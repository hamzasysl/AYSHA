<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SalaryPayment extends Model
{
    use HasFactory;

    public const STATUSES = [
        'pending' => 'Bekliyor',
        'partial' => 'Kısmi',
        'paid' => 'Ödendi',
    ];

    public const METHODS = [
        'transfer' => 'Havale / EFT',
        'cash' => 'Nakit',
    ];

    public const MONTHS = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
        7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
    ];

    protected $fillable = [
        'employee_id', 'period_year', 'period_month', 'base_salary', 'bonus', 'deduction',
        'advance', 'net_amount', 'paid_amount', 'status', 'payment_method', 'paid_at', 'note',
        'refund_amount', 'refund_at',
    ];

    protected function casts(): array
    {
        return [
            'base_salary' => 'decimal:2',
            'bonus' => 'decimal:2',
            'deduction' => 'decimal:2',
            'advance' => 'decimal:2',
            'net_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'paid_at' => 'date',
            'refund_amount' => 'decimal:2',
            'refund_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        // Net tutar ve durum her kayıtta otomatik hesaplanır.
        static::saving(function (SalaryPayment $payment) {
            $payment->net_amount = round(
                (float) $payment->base_salary + (float) $payment->bonus
                - (float) $payment->deduction - (float) $payment->advance,
                2
            );

            $paid = (float) $payment->paid_amount;

            $payment->status = match (true) {
                $paid <= 0 => 'pending',
                $paid + 0.005 >= (float) $payment->net_amount => 'paid',
                default => 'partial',
            };

            if ($payment->status === 'pending') {
                $payment->paid_at = null;
            } elseif (! $payment->paid_at) {
                $payment->paid_at = now()->toDateString();
            }

            // İade, fazla ödenen tutarı aşamaz; fazla ödeme yoksa iade sıfırlanır.
            $over = max(0, round($paid - (float) $payment->net_amount, 2));
            $payment->refund_amount = min((float) $payment->refund_amount, $over);
            if ((float) $payment->refund_amount <= 0) {
                $payment->refund_amount = 0;
                $payment->refund_at = null;
            } elseif (! $payment->refund_at) {
                $payment->refund_at = now()->toDateString();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function scopeForPeriod(Builder $query, int $year, int $month): Builder
    {
        return $query->where('period_year', $year)->where('period_month', $month);
    }

    public function getPeriodLabelAttribute(): string
    {
        return (self::MONTHS[$this->period_month] ?? $this->period_month).' '.$this->period_year;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getMethodLabelAttribute(): ?string
    {
        return $this->payment_method ? (self::METHODS[$this->payment_method] ?? $this->payment_method) : null;
    }

    public function getRemainingAttribute(): float
    {
        return max(0, round((float) $this->net_amount - (float) $this->paid_amount, 2));
    }

    /** Net tutarın üstünde ödenen (para üstü). */
    public function getOverpaidAttribute(): float
    {
        return max(0, round((float) $this->paid_amount - (float) $this->net_amount, 2));
    }

    /** Personelden geri alınması beklenen. */
    public function getRefundPendingAttribute(): float
    {
        return max(0, round($this->overpaid - (float) $this->refund_amount, 2));
    }
}
