<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeaveRequest;
use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        if ($year < 2020 || $year > 2100) {
            $year = (int) now()->year;
        }
        $type = $request->get('type');
        $employeeId = $request->integer('employee_id') ?: null;

        $employees = Employee::active()->orderBy('first_name')->orderBy('last_name')->get();
        $yearLeaves = Leave::forYear($year)->with(['employee', 'author', 'notes'])->get();

        // Yıllık izin bakiyesi tablosu
        $balances = $employees->map(function (Employee $e) use ($yearLeaves) {
            $mine = $yearLeaves->where('employee_id', $e->id);
            $used = (float) $mine->where('deduct_annual', true)->sum('days');

            return [
                'employee' => $e,
                'entitlement' => (float) $e->annual_leave_days,
                'used' => $used,
                'remaining' => round((float) $e->annual_leave_days - $used, 1),
                'other' => (float) $mine->where('deduct_annual', false)->whereNotIn('type', ['sick', 'absence'])->sum('days'),
                'sick' => (float) $mine->where('type', 'sick')->sum('days'),
                'absence' => (float) $mine->where('type', 'absence')->sum('days'),
            ];
        });

        $leaves = $yearLeaves
            ->when($type && isset(Leave::types()[$type]), fn ($c) => $c->where('type', $type))
            ->when($employeeId, fn ($c) => $c->where('employee_id', $employeeId))
            ->sortByDesc('start_date')->values();

        $today = now()->startOfDay();
        $onLeaveToday = Leave::onDate($today)->with('employee')->get();

        $stats = [
            'on_leave_today' => $onLeaveToday->count(),
            'annual_used' => (float) $yearLeaves->where('deduct_annual', true)->sum('days'),
            'sick' => (float) $yearLeaves->where('type', 'sick')->sum('days'),
            'absence' => (float) $yearLeaves->where('type', 'absence')->sum('days'),
            'exhausted' => $balances->filter(fn ($b) => $b['remaining'] <= 0)->count(),
        ];

        $allEmployees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'status']);

        return view('leaves.index', compact('year', 'type', 'employeeId', 'balances', 'leaves', 'stats', 'onLeaveToday', 'allEmployees'));
    }

    public function create(Request $request)
    {
        $leave = new Leave([
            'employee_id' => $request->integer('employee_id') ?: null,
            'type' => $request->get('type', 'annual'),
            'leave_year' => (int) $request->get('year', now()->year),
            'start_date' => now()->toDateString(),
            'end_date' => now()->toDateString(),
            'deduct_annual' => true,
            'days' => 1,
        ]);

        return view('leaves.create', ['leave' => $leave] + $this->formData());
    }

    public function store(LeaveRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $force = $data['force'] ?? false;
        unset($data['force']);

        $employee = Employee::findOrFail($data['employee_id']);
        $data['return_date'] = $data['return_date'] ?? Leave::nextWorkday(Carbon::parse($data['end_date']))->toDateString();

        if ($data['deduct_annual']) {
            $balance = $employee->leaveBalance((int) $data['leave_year']);
            if ((float) $data['days'] > $balance['remaining'] && ! $force) {
                return back()->withInput()->with('leave_warning', [
                    'remaining' => $balance['remaining'], 'requested' => (float) $data['days'], 'employee' => $employee->full_name,
                ]);
            }
        }

        $leave = Leave::create($data + ['user_id' => $request->user()->id]);

        return redirect()->route('leaves.index', ['year' => $leave->leave_year])
            ->with('success', $employee->full_name.' için '.$leave->type_label.' kaydedildi ('.rtrim(rtrim(number_format($leave->days, 1, ',', ''), '0'), ',').' iş günü).');
    }

    public function edit(Leave $leave)
    {
        return view('leaves.edit', ['leave' => $leave] + $this->formData());
    }

    public function update(LeaveRequest $request, Leave $leave): RedirectResponse
    {
        $data = $request->validated();
        $force = $data['force'] ?? false;
        unset($data['force']);
        $employee = Employee::withTrashed()->findOrFail($data['employee_id']);

        if ($data['deduct_annual']) {
            $balance = $employee->leaveBalance((int) $data['leave_year']);
            // Bu kaydın mevcut düşümünü bakiyeye geri ekle
            $current = ($leave->deduct_annual && (int) $leave->leave_year === (int) $data['leave_year']) ? (float) $leave->days : 0;
            if ((float) $data['days'] > $balance['remaining'] + $current && ! $force) {
                return back()->withInput()->with('leave_warning', [
                    'remaining' => $balance['remaining'] + $current, 'requested' => (float) $data['days'], 'employee' => $employee->full_name,
                ]);
            }
        }

        $leave->update($data);

        return redirect()->route('leaves.index', ['year' => $leave->leave_year])->with('success', 'İzin kaydı güncellendi.');
    }

    public function destroy(Leave $leave): RedirectResponse
    {
        $leave->delete();

        return back()->with('success', 'İzin kaydı silindi.');
    }

    /** TTB Grup İzin Formu (yazdırılabilir). */
    public function print(Leave $leave)
    {
        $leave->load('employee');
        $balance = $leave->employee->leaveBalance((int) $leave->leave_year);

        return view('leaves.print', compact('leave', 'balance'));
    }

    /** Form için personel listesi + yıllık bakiyeler (uyarı hesabı için). */
    private function formData(): array
    {
        $employees = Employee::active()->orderBy('first_name')->get();
        $years = collect(range(now()->year - 1, now()->year + 1));
        $balances = [];
        foreach ($employees as $e) {
            foreach ($years as $y) {
                $balances[$e->id][$y] = $e->leaveBalance($y)['remaining'];
            }
        }

        return compact('employees', 'balances');
    }
}
