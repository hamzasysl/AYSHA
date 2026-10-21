<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Anlaşma hareketi: alınan ödeme, ekstra gelir ya da ekstra gider. */
class AgreementEntry extends Model
{
    use HasFactory;

    public const KINDS = [
        'payment' => 'Ödeme alındı',
        'income' => 'Ekstra gelir',
        'expense' => 'Ekstra gider',
    ];

    protected $fillable = ['agreement_id', 'entry_date', 'kind', 'label', 'amount'];

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

    /** Gider eksi, diğerleri artı. */
    public function getSignedAmountAttribute(): float
    {
        return round((float) $this->amount * ($this->kind === 'expense' ? -1 : 1), 2);
    }
}
