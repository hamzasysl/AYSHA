<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\PerformanceReview;
use App\Models\SalaryPayment;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $year = (int) $request->get('year', now()->year);

        $years = SalaryPayment::select('period_year')->distinct()->pluck('period_year')->map(fn ($v) => (int) $v)
            ->merge(Expense::pluck('expense_date')->map(fn ($d) => (int) $d->year))
            ->push((int) now()->year)->unique()->sortDesc()->values();

        $cats = Expense::categories();
        $isExtra = fn ($slug) => ! in_array($slug, ['meal', 'travel'], true) && ($cats[$slug]['employee_based'] ?? false);
        $isGeneral = fn ($slug) => ! ($cats[$slug]['employee_based'] ?? false);
        $payments = SalaryPayment::where('period_year', $year)->with('employee')->get();
        $expenses = Expense::whereBetween('expense_date', ["{$year}-01-01", "{$year}-12-31"])->with('employee')->get();

        // Aylık özet: maaş + her gider kategorisi + kesintiler + ödeme durumu
        $monthly = collect(range(1, 12))->map(function (int $m) use ($payments, $expenses, $isExtra, $isGeneral) {
            $p = $payments->where('period_month', $m);
            $x = $expenses->filter(fn ($e) => (int) $e->expense_date->month === $m);
            $cat = fn ($c) => (float) $x->where('category', $c)->sum(fn ($e) => $e->net_amount);
            $salaryNet = (float) $p->sum('net_amount');
            $expNet = (float) $x->sum(fn ($e) => $e->net_amount);
            $paid = (float) $p->sum('paid_amount') + (float) $x->where('status', 'paid')->sum(fn ($e) => $e->net_amount);
            $total = $salaryNet + $expNet;

            return [
                'month' => $m, 'label' => SalaryPayment::MONTHS[$m],
                'has' => $p->isNotEmpty() || $x->isNotEmpty(),
                'employees' => $p->count(),
                'salary_base' => (float) $p->sum('base_salary'),
                'salary_bonus' => (float) $p->sum('bonus'),
                'salary_deduction' => (float) $p->sum('deduction') + (float) $p->sum('advance'),
                'salary_net' => $salaryNet,
                'meal' => $cat('meal'), 'travel' => $cat('travel'),
                'extra' => (float) $x->filter(fn ($e) => $isExtra($e->category))->sum(fn ($e) => $e->net_amount),
                'general' => (float) $x->filter(fn ($e) => $isGeneral($e->category))->sum(fn ($e) => $e->net_amount),
                'expense_deduction' => (float) $x->sum('deduction'),
                'total' => $total,
                'paid' => $paid,
                'remaining' => max(0, $total - $paid),
                'ratio' => $total > 0 ? (int) round($paid / $total * 100) : null,
                'refund_pending' => (float) $p->sum(fn ($i) => $i->refund_pending) + (float) $x->sum(fn ($i) => $i->refund_pending),
                'pending' => [
                    'salary' => $p->where('status', '!=', 'paid')->count(),
                    'meal' => $x->where('category', 'meal')->where('status', 'pending')->count(),
                    'travel' => $x->where('category', 'travel')->where('status', 'pending')->count(),
                    'extra' => $x->filter(fn ($e) => $isExtra($e->category))->where('status', 'pending')->count(),
                    'general' => $x->filter(fn ($e) => $isGeneral($e->category))->where('status', 'pending')->count(),
                ],
            ];
        });

        $totals = [
            'salary_net' => $monthly->sum('salary_net'), 'salary_bonus' => $monthly->sum('salary_bonus'), 'salary_deduction' => $monthly->sum('salary_deduction'),
            'meal' => $monthly->sum('meal'), 'travel' => $monthly->sum('travel'), 'extra' => $monthly->sum('extra'), 'general' => $monthly->sum('general'),
            'expense_deduction' => $monthly->sum('expense_deduction'),
            'total' => $monthly->sum('total'), 'paid' => $monthly->sum('paid'), 'remaining' => $monthly->sum('remaining'),
            'refund_pending' => $monthly->sum('refund_pending'),
            'max_total' => max(1, $monthly->max('total')),
        ];
        $totals['ratio'] = $totals['total'] > 0 ? (int) round($totals['paid'] / $totals['total'] * 100) : null;

        // Maliyet dağılımı (yıl): maaş + her kategori
        $colorHex = ['brand' => '#1e6ff2', 'amber' => '#f59e0b', 'sky' => '#0ea5e9', 'emerald' => '#10b981', 'violet' => '#a855f7', 'rose' => '#f43f5e', 'orange' => '#f97316', 'slate' => '#64748b'];
        $breakdown = collect([['label' => 'Maaş', 'value' => $totals['salary_net'], 'color' => '#1e6ff2', 'icon' => 'fa-money-bill-transfer']]);
        foreach ($cats as $slug => $c) {
            $v = (float) $expenses->where('category', $slug)->sum(fn ($e) => $e->net_amount);
            if ($v > 0) {
                $breakdown->push(['label' => $c['label'], 'value' => $v, 'color' => $colorHex[$c['color']] ?? '#64748b', 'icon' => $c['icon']]);
            }
        }
        $breakdown = $breakdown->map(fn ($b) => $b + ['pct' => $totals['total'] > 0 ? round($b['value'] / $totals['total'] * 100) : 0]);

        // Personel bazlı yıllık: maaş + yemek + yol + diğer + kesinti + performans
        $reviews = PerformanceReview::where('period_year', $year)->get();
        $employeeIds = $payments->pluck('employee_id')->merge($expenses->pluck('employee_id'))->merge($reviews->pluck('employee_id'))->filter()->unique();
        $employees = Employee::withTrashed()->whereIn('id', $employeeIds)->orderBy('first_name')->get()->map(function (Employee $e) use ($payments, $expenses, $reviews, $isExtra, $isGeneral) {
            $p = $payments->where('employee_id', $e->id);
            $x = $expenses->where('employee_id', $e->id);
            $r = $reviews->where('employee_id', $e->id);
            $cat = fn ($c) => (float) $x->where('category', $c)->sum(fn ($i) => $i->net_amount);
            $total = (float) $p->sum('net_amount') + (float) $x->sum(fn ($i) => $i->net_amount);
            $paid = (float) $p->sum('paid_amount') + (float) $x->where('status', 'paid')->sum(fn ($i) => $i->net_amount);

            return [
                'employee' => $e, 'months' => $p->count(),
                'salary' => (float) $p->sum('net_amount'), 'bonus' => (float) $p->sum('bonus'),
                'deduction' => (float) $p->sum('deduction') + (float) $p->sum('advance') + (float) $x->sum('deduction'),
                'meal' => $cat('meal'), 'travel' => $cat('travel'),
                'extra' => (float) $x->filter(fn ($i) => $isExtra($i->category))->sum(fn ($i) => $i->net_amount),
                'extra_detail' => $x->filter(fn ($i) => $isExtra($i->category))->groupBy('category')->map(fn ($g) => (float) $g->sum(fn ($i) => $i->net_amount)),
                'other' => (float) $x->filter(fn ($i) => $isGeneral($i->category))->sum(fn ($i) => $i->net_amount),
                'total' => $total, 'paid' => $paid, 'remaining' => max(0, $total - $paid),
                'refund_pending' => (float) $p->sum(fn ($i) => $i->refund_pending) + (float) $x->sum(fn ($i) => $i->refund_pending),
                'pending_count' => $p->where('status', '!=', 'paid')->count() + $x->where('status', 'pending')->count(),
                'avg_score' => $r->isNotEmpty() ? round($r->avg('score'), 1) : null, 'reviews' => $r->count(),
            ];
        });

        $reviewStats = [
            'count' => $reviews->count(),
            'avg' => $reviews->isNotEmpty() ? round($reviews->avg('score'), 1) : null,
            'top' => $employees->filter(fn ($r) => $r['avg_score'] !== null)->sortByDesc('avg_score')->take(3),
            'low' => $employees->filter(fn ($r) => $r['avg_score'] !== null && $r['avg_score'] < 6)->sortBy('avg_score')->take(3),
        ];

        return view('reports.index', compact('year', 'years', 'monthly', 'totals', 'breakdown', 'employees', 'reviewStats'));
    }
}
