<?php

namespace App\Models;

use App\Models\Concerns\HasNotes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Expense extends Model
{
    use HasFactory, HasNotes;

    /** Kategoriler veritabanından (Ayarlar > Gider Kategorileri). slug => [label, icon, color, employee_based] */
    public static function categories(): array
    {
        return ExpenseCategory::map();
    }

    public const STATUSES = ['pending' => 'Bekliyor', 'paid' => 'Ödendi'];

    protected $fillable = [
        'employee_id', 'category', 'expense_date', 'description', 'amount', 'deduction', 'deduction_note',
        'status', 'payment_method', 'paid_at', 'paid_amount', 'refund_amount', 'refund_at',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'paid_at' => 'date',
            'amount' => 'decimal:2',
            'deduction' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'refund_amount' => 'decimal:2',
            'refund_at' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Expense $e) {
            if ($e->status === 'paid' && ! $e->paid_at) {
                $e->paid_at = now()->toDateString();
            }
            $e->deduction = max(0, min((float) $e->deduction, (float) $e->amount));

            if ($e->status === 'pending') {
                $e->paid_at = null;
                $e->paid_amount = null;
            } elseif ($e->paid_amount === null || (float) $e->paid_amount <= 0) {
                $e->paid_amount = $e->net_amount;
            }

            $over = max(0, round((float) ($e->paid_amount ?? 0) - $e->net_amount, 2));
            $e->refund_amount = min((float) $e->refund_amount, $over);
            if ((float) $e->refund_amount <= 0) {
                $e->refund_amount = 0;
                $e->refund_at = null;
            } elseif (! $e->refund_at) {
                $e->refund_at = now()->toDateString();
            }
        });
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function scopeForPeriod(Builder $q, int $year, int $month): Builder
    {
        $start = Carbon::create($year, $month, 1);

        return $q->whereBetween('expense_date', [$start->toDateString(), $start->copy()->endOfMonth()->toDateString()]);
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::categories()[$this->category]['label'] ?? $this->category;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Ödenecek net: tutar − kesinti. */
    public function getNetAmountAttribute(): float
    {
        return max(0, round((float) $this->amount - (float) $this->deduction, 2));
    }

    public function getOverpaidAttribute(): float
    {
        return max(0, round((float) ($this->paid_amount ?? 0) - $this->net_amount, 2));
    }

    public function getRefundPendingAttribute(): float
    {
        return max(0, round($this->overpaid - (float) $this->refund_amount, 2));
    }

    public function getMethodLabelAttribute(): ?string
    {
        return $this->payment_method ? (SalaryPayment::METHODS[$this->payment_method] ?? null) : null;
    }
}
