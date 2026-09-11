<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\SalaryPayment;
use App\Support\PayrollPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }
        if ($year < 2020 || $year > 2100) {
            $year = (int) now()->year;
        }

        $category = $request->get('category');
        $status = $request->get('status');
        $employeeId = $request->integer('employee_id') ?: null;

        // Maaşlar (bordro) — aynı sayfada sekme
        $payroll = PayrollPeriod::data($year, $month, $category === 'salary' ? $status : null);

        $all = Expense::forPeriod($year, $month)->with(['employee', 'notes'])->orderByDesc('expense_date')->orderByDesc('id')->get();

        $expenses = $all
            ->when($category && isset(Expense::categories()[$category]), fn ($c) => $c->where('category', $category))
            ->when($employeeId, fn ($c) => $c->where('employee_id', $employeeId))
            ->values();

        $byCategory = collect(Expense::categories())->map(fn ($meta, $key) => [
            'label' => $meta['label'], 'icon' => $meta['icon'], 'color' => $meta['color'],
            'total' => (float) $all->where('category', $key)->sum(fn ($x) => $x->net_amount),
            'gross' => (float) $all->where('category', $key)->sum('amount'),
            'deduction' => (float) $all->where('category', $key)->sum('deduction'),
            'count' => $all->where('category', $key)->count(),
            'pending' => (float) $all->where('category', $key)->where('status', 'pending')->sum(fn ($x) => $x->net_amount),
        ]);

        $pt = $payroll['totals'];
        $totals = [
            'count' => $all->count(),
            'total' => (float) $all->sum(fn ($x) => $x->net_amount),
            'deduction' => (float) $all->sum('deduction'),
            'paid' => (float) $all->where('status', 'paid')->sum(fn ($x) => $x->net_amount),
            'pending' => (float) $all->where('status', 'pending')->sum(fn ($x) => $x->net_amount),
            'pending_count' => $all->where('status', 'pending')->count(),
            'refund_pending' => (float) $all->sum(fn ($x) => $x->refund_pending) + $pt['refund_pending'],
            'refund_pending_count' => $all->filter(fn ($x) => $x->refund_pending > 0)->count() + $pt['refund_pending_count'],
            'payroll' => $pt['net'],
            'payroll_paid' => $pt['paid'],
            'payroll_remaining' => $pt['remaining'],
            'payroll_count' => $pt['count'],
            // Dönem geneli: maaş + tüm giderler
            'grand' => $pt['net'] + (float) $all->sum(fn ($x) => $x->net_amount),
            'grand_paid' => $pt['paid'] + (float) $all->where('status', 'paid')->sum(fn ($x) => $x->net_amount),
            'grand_remaining' => $pt['remaining'] + (float) $all->where('status', 'pending')->sum(fn ($x) => $x->net_amount),
        ];

        // Personel bazlı özet (yemek + yol + diğer)
        $perEmployee = $all->whereNotNull('employee_id')->groupBy('employee_id')->map(function ($rows) {
            $e = $rows->first()->employee;

            return [
                'employee' => $e,
                'meal' => (float) $rows->where('category', 'meal')->sum(fn ($x) => $x->net_amount),
                'travel' => (float) $rows->where('category', 'travel')->sum(fn ($x) => $x->net_amount),
                'cleaning' => (float) $rows->where('category', 'cleaning')->sum(fn ($x) => $x->net_amount),
                'other' => (float) $rows->where('category', 'other')->sum(fn ($x) => $x->net_amount),
                'total' => (float) $rows->sum(fn ($x) => $x->net_amount),
                'pending' => (float) $rows->where('status', 'pending')->sum(fn ($x) => $x->net_amount),
            ];
        })->sortBy(fn ($r) => $r['employee']->first_name)->values();

        $employees = Employee::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'status']);

        return view('expenses.index', compact('expenses', 'byCategory', 'totals', 'perEmployee', 'employees', 'year', 'month', 'category', 'employeeId', 'status') + [
            'payments' => $payroll['payments'], 'payrollTotals' => $payroll['totals'], 'missing' => $payroll['missing'],
        ]);
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $note = $data['note'] ?? null;
        $ids = $data['employee_ids'] ?? [];
        unset($data['note'], $data['employee_ids']);

        // Birden fazla personel seçildiyse her birine ayrı kayıt (örn. 10 kişiye 400 ₺ sağlık raporu ücreti)
        $targets = $ids ?: [$data['employee_id'] ?? null];
        $last = null;
        foreach ($targets as $employeeId) {
            $last = Expense::create(array_merge($data, ['employee_id' => $employeeId]));
            if ($note) {
                $last->notes()->create(['content' => $note, 'user_id' => $request->user()->id]);
            }
        }

        return redirect()->route('expenses.index', ['year' => $last->expense_date->year, 'month' => $last->expense_date->month])
            ->with('success', count($targets) > 1 ? count($targets).' personele '.$last->category_label.' kaydı eklendi.' : $last->category_label.' kaydı eklendi.');
    }

    /** Aynı kategoride birden fazla personele tek seferde kayıt açar (örn. tüm ekibe yemek ücreti). */
    public function bulkStore(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'employee_ids' => ['required', 'array', 'min:1'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'category' => ['required', 'in:'.implode(',', array_keys(Expense::categories()))],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'string'],
            'status' => ['required', 'in:pending,paid'],
        ], [], ['employee_ids' => 'personel', 'amount' => 'tutar', 'expense_date' => 'tarih']);

        $amount = (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', $data['amount']));
        abort_if($amount <= 0, 422, 'Tutar sıfırdan büyük olmalı.');

        foreach ($data['employee_ids'] as $id) {
            Expense::create([
                'employee_id' => $id,
                'category' => $data['category'],
                'expense_date' => $data['expense_date'],
                'description' => $data['description'] ?? null,
                'amount' => $amount,
                'status' => $data['status'],
            ]);
        }

        $d = \Illuminate\Support\Carbon::parse($data['expense_date']);

        return redirect()->route('expenses.index', ['year' => $d->year, 'month' => $d->month])
            ->with('success', count($data['employee_ids']).' personele '.Expense::categories()[$data['category']]['label'].' kaydı açıldı.');
    }

    /** Seçilen ay için tüm aktif personele yemek ücreti ve yol parası kaydı açar (o ay zaten varsa atlar). */
    public function generateAllowances(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2020,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
            'status' => ['nullable', 'in:pending,paid'],
        ]);

        $date = \Illuminate\Support\Carbon::create($data['year'], $data['month'], 1);
        $label = SalaryPayment::MONTHS[(int) $data['month']];
        $existing = Expense::forPeriod($date->year, $date->month)->whereIn('category', ['meal', 'travel'])->get()
            ->map(fn ($x) => $x->employee_id.':'.$x->category)->flip();

        $created = 0;
        Employee::active()->orderBy('first_name')->each(function (Employee $e) use ($date, $label, $existing, $data, &$created) {
            foreach (['meal' => [$e->effective_meal_allowance, "{$label} yemek ücreti"], 'travel' => [$e->effective_travel_allowance, "{$label} yol parası"]] as $cat => [$amount, $desc]) {
                if ($amount <= 0 || isset($existing[$e->id.':'.$cat])) {
                    continue;
                }
                Expense::create([
                    'employee_id' => $e->id, 'category' => $cat, 'expense_date' => $date->toDateString(),
                    'description' => $desc.($e->is_retired ? ' (emekli)' : ''), 'amount' => $amount,
                    'status' => $data['status'] ?? 'pending',
                ]);
                $created++;
            }
        });

        return redirect()->route('expenses.index', ['year' => $date->year, 'month' => $date->month])
            ->with('success', $created > 0 ? "{$label} için {$created} yemek / yol kaydı oluşturuldu." : "{$label} ayında eklenecek yeni yemek / yol kaydı yok.");
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $data = $request->validated();
        unset($data['note']);
        $data['deduction'] = $data['deduction'] ?? 0;
        $expense->update($data);

        return back()->with('success', 'Kayıt güncellendi.');
    }

    /** Durum açılır menüsü: bekliyor <-> ödendi. */
    public function setStatus(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,paid']]);

        if ($data['status'] === 'paid') {
            $expense->status = 'paid';
            $expense->payment_method = $expense->payment_method ?? 'transfer';
            $expense->paid_at = $expense->paid_at ?? now()->toDateString();
        } else {
            $expense->status = 'pending';
        }
        $expense->save();

        return back()->with('success', ($expense->employee?->full_name ?? 'Genel gider').' · '.$expense->category_label.' → '.$expense->status_label);
    }

    public function markPaid(Request $request, Expense $expense): RedirectResponse
    {
        $data = $request->validate(['payment_method' => ['nullable', 'in:transfer,cash']]);

        $expense->status = 'paid';
        $expense->payment_method = $data['payment_method'] ?? $expense->payment_method ?? 'transfer';
        $expense->paid_at = now()->toDateString();
        $expense->save();

        return back()->with('success', 'Ödendi olarak işaretlendi.');
    }

    public function refundReceived(Request $request, Expense $expense): RedirectResponse
    {
        abort_if($expense->overpaid <= 0, 422, 'Bu kayıtta fazla ödeme yok.');

        $expense->refund_amount = $expense->overpaid;
        $expense->refund_at = $request->input('refund_at') ?: now()->toDateString();
        $expense->save();

        return back()->with('success', number_format($expense->overpaid, 2, ',', '.').' ₺ iade alındı olarak işaretlendi.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return back()->with('success', 'Kayıt silindi.');
    }
}
