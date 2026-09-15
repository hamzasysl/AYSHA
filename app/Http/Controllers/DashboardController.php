<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\Note;
use App\Models\PerformanceReview;
use App\Models\SalaryPayment;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $year = (int) now()->year;
        $month = (int) now()->month;

        $payments = SalaryPayment::forPeriod($year, $month)->with('employee')->get();
        $expenses = Expense::forPeriod($year, $month)->get();

        $stats = [
            'active' => Employee::active()->count(),
            'passive' => Employee::where('status', 'passive')->count(),
            'monthly_payroll' => Employee::active()->sum('salary'),
            'period_net' => $payments->sum('net_amount'),
            'period_paid' => $payments->sum('paid_amount'),
            'period_pending_count' => $payments->where('status', '!=', 'paid')->count(),
            'refund_pending' => $payments->sum(fn ($p) => $p->refund_pending) + $expenses->sum(fn ($x) => $x->refund_pending),
            'period_generated' => $payments->isNotEmpty(),
            'period_expenses' => $expenses->sum('amount'),
            'period_expenses_pending' => $expenses->where('status', 'pending')->sum('amount'),
            'avg_score' => round((float) PerformanceReview::where('review_date', '>=', now()->subMonths(3))->avg('score'), 1),
        ];

        $pendingPayments = $payments->where('status', '!=', 'paid')->sortBy(fn ($p) => $p->employee->last_name)->take(8);

        // Silinmiş personele/kayda bağlı satırları gösterme (linki 404 veriyordu)
        $recentReviews = PerformanceReview::whereHas('employee', fn ($q) => $q->withoutTrashed())->with('employee')->latest('review_date')->latest('id')->take(5)->get();
        $recentNotes = Note::with(['notable', 'author'])->latest()->latest('id')->take(30)->get()
            ->filter(fn (Note $n) => $n->notable !== null)->take(6)->values();

        // Son 6 ayın ödeme özeti (rapor mini grafiği)
        $trend = collect(range(5, 0))->map(function (int $back) {
            $d = now()->startOfMonth()->subMonths($back);
            $rows = SalaryPayment::forPeriod($d->year, $d->month)->get();

            return [
                'label' => SalaryPayment::MONTHS[$d->month].' '.$d->format('y'),
                'net' => (float) $rows->sum('net_amount'),
                'paid' => (float) $rows->sum('paid_amount'),
            ];
        });

        return view('dashboard', compact('stats', 'pendingPayments', 'recentReviews', 'recentNotes', 'trend', 'year', 'month'));
    }
}
