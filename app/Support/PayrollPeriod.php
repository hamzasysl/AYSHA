<?php

namespace App\Support;

use App\Models\Employee;
use App\Models\SalaryPayment;

/** Bir dönemin bordro verisi (Muhasebe > Maaşlar sekmesi ve dönem toplamları). */
class PayrollPeriod
{
    /** @return array{payments: \Illuminate\Support\Collection, totals: array, missing: \Illuminate\Support\Collection} */
    public static function data(int $year, int $month, ?string $status = null): array
    {
        $all = SalaryPayment::forPeriod($year, $month)->with('employee')->get()
            ->sortBy(fn ($p) => $p->employee->first_name.' '.$p->employee->last_name)->values();

        $payments = $status && array_key_exists($status, SalaryPayment::STATUSES)
            ? $all->where('status', $status)->values()
            : $all;

        $totals = [
            'count' => $all->count(),
            'net' => (float) $all->sum('net_amount'),
            'paid' => (float) $all->sum('paid_amount'),
            'remaining' => (float) $all->sum(fn ($p) => $p->remaining),
            'paid_count' => $all->where('status', 'paid')->count(),
            'partial_count' => $all->where('status', 'partial')->count(),
            'pending_count' => $all->where('status', 'pending')->count(),
            'overpaid' => (float) $all->sum(fn ($p) => $p->overpaid),
            'refund_pending' => (float) $all->sum(fn ($p) => $p->refund_pending),
            'refund_pending_count' => $all->filter(fn ($p) => $p->refund_pending > 0)->count(),
        ];

        $missing = Employee::active()
            ->whereNotIn('id', $all->pluck('employee_id'))
            ->orderBy('first_name')->get();

        return compact('payments', 'totals', 'missing');
    }
}
