<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeRequest;
use App\Models\Employee;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $search = $request->get('q');
        $type = $request->get('type'); // retired | normal

        $employees = Employee::query()
            ->when($status && array_key_exists($status, Employee::STATUSES), fn ($q) => $q->where('status', $status))
            ->when($type === 'retired', fn ($q) => $q->where('is_retired', true))
            ->when($type === 'normal', fn ($q) => $q->where('is_retired', false))
            ->search($search)
            ->withCount('notes')
            ->withSum(['leaves as annual_used' => fn ($q) => $q->where('leave_year', now()->year)->where('deduct_annual', true)], 'days')
            ->withAvg('performanceReviews as avg_score', 'score')
            ->orderBy('first_name')->orderBy('last_name')
            ->paginate(25)
            ->withQueryString();

        $stats = [
            'total' => Employee::count(),
            'active' => Employee::active()->count(),
            'passive' => Employee::where('status', 'passive')->count(),
            'retired' => Employee::where('is_retired', true)->count(),
            'normal' => Employee::where('is_retired', false)->count(),
        ];

        return view('employees.index', compact('employees', 'stats', 'status', 'search', 'type'));
    }

    public function create()
    {
        return view('employees.create', ['employee' => new Employee(['status' => 'active', 'position' => Employee::positions()[0]])]);
    }

    public function store(EmployeeRequest $request): RedirectResponse
    {
        $employee = Employee::create($request->validated());

        return redirect()->route('employees.show', $employee)->with('success', 'Personel kaydı oluşturuldu.');
    }

    public function show(Employee $employee)
    {
        $employee->load(['notes', 'performanceReviews.reviewer', 'expenses.notes', 'leaves.notes']);
        $leaveBalance = $employee->leaveBalance((int) now()->year);
        $payments = $employee->salaryPayments()->get();

        $summary = [
            'total_paid' => $payments->sum('paid_amount'),
            'total_pending' => $payments->where('status', '!=', 'paid')->sum(fn ($p) => $p->remaining),
            'avg_score' => $employee->average_score,
            'review_count' => $employee->performanceReviews->count(),
            'tenure' => $employee->hire_date ? $employee->hire_date->diffForHumans(null, true) : null,
            'expenses_total' => $employee->expenses->sum(fn ($x) => $x->net_amount),
            'expenses_pending' => $employee->expenses->where('status', 'pending')->sum(fn ($x) => $x->net_amount),
            'extra_total' => $employee->expenses->where('category', 'extra')->sum(fn ($x) => $x->net_amount),
        ];

        return view('employees.show', compact('employee', 'payments', 'summary', 'leaveBalance'));
    }

    public function edit(Employee $employee)
    {
        return view('employees.edit', compact('employee'));
    }

    public function update(EmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $employee->update($request->validated());

        return redirect()->route('employees.show', $employee)->with('success', 'Personel bilgileri güncellendi.');
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $employee->delete();

        return redirect()->route('employees.index')->with('success', 'Personel kaydı silindi.');
    }
}
