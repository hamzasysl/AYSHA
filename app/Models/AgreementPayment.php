<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Kurumun bir dönem için ödediği (ya da ödeyeceği) tutar. */
class AgreementPayment extends Model
{
    use HasFactory;

    public const STATUSES = ['pending' => 'Bekliyor', 'paid' => 'Tahsil edildi'];

    public const METHODS = ['transfer' => 'Havale / EFT', 'cash' => 'Nakit', 'check' => 'Çek'];

    protected $fillable = ['agreement_id', 'period_year', 'period_month', 'amount', 'status', 'paid_at', 'method', 'note'];

    protected function casts(): array
    {
        return ['paid_at' => 'date', 'amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        static::saving(function (AgreementPayment $p) {
            if ($p->status === 'paid') {
                $p->paid_at = $p->paid_at ?: now()->toDateString();
                $p->method = $p->method ?: 'transfer';
            } else {
                $p->paid_at = null;
                $p->method = null;
            }
        });
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getMethodLabelAttribute(): ?string
    {
        return $this->method ? (self::METHODS[$this->method] ?? null) : null;
    }

    public function getPeriodLabelAttribute(): string
    {
        return $this->period_month
            ? SalaryPayment::MONTHS[(int) $this->period_month].' '.$this->period_year
            : (string) $this->period_year;
    }
}
