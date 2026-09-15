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

    public const METHODS = ['transfer' => 'Havale / EFT', 'cash' => 'Nakit', 'meal_card' => 'Yemek Kartı'];

    /** Kategoriye göre varsayılan ödeme yöntemi: yol elden nakit, yemek yemek kartına yüklenir, kalanı EFT. */
    public const DEFAULT_METHODS = ['travel' => 'cash', 'meal' => 'meal_card'];

    public static function defaultMethod(?string $category): string
    {
        return self::DEFAULT_METHODS[$category] ?? 'transfer';
    }

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

            // "Fiilen ödenen" girildiyse kayıt ödendi sayılır; durumu ayrıca çevirmeye gerek yok
            // (elden yuvarlak tutar veriliyor, para üstü sonradan iade alınıyor).
            if ($e->status === 'pending' && (float) $e->paid_amount > 0 && $e->isDirty('paid_amount')) {
                $e->status = 'paid';
            }

            if ($e->status === 'pending') {
                $e->paid_at = null;
                $e->paid_amount = null;
                $e->payment_method = null;
            } else {
                if ($e->paid_amount === null || (float) $e->paid_amount <= 0) {
                    $e->paid_amount = $e->net_amount;
                }
                if (! $e->payment_method) {
                    $e->payment_method = self::defaultMethod($e->category);
                }
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

        static::deleting(fn (Expense $e) => $e->deleteNotes());
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
        return $this->payment_method ? (self::METHODS[$this->payment_method] ?? null) : null;
    }
}
