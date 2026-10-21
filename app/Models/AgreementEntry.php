<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Anlaşma hareketi: şirketin yaptığı ödeme, ekstra gelir ya da ekstra gider. */
class AgreementEntry extends Model
{
    use HasFactory;

    public const KINDS = [
        'payment' => 'Ödeme',
        'income' => 'Ekstra gelir',
        'expense' => 'Ekstra gider',
    ];

    public const STATUSES = ['done' => 'Yapıldı', 'pending' => 'Ödeme bekleniyor'];

    protected $fillable = ['agreement_id', 'entry_date', 'kind', 'status', 'label', 'amount'];

    protected function casts(): array
    {
        return ['entry_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getIsDoneAttribute(): bool
    {
        return $this->status === 'done';
    }

    /** Gider eksi, diğerleri artı. Bekleyen kayıtlar toplama girmez. */
    public function getSignedAmountAttribute(): float
    {
        if (! $this->is_done) {
            return 0.0;
        }

        return round((float) $this->amount * ($this->kind === 'expense' ? -1 : 1), 2);
    }
}
