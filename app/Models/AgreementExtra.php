<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Anlaşmaya bağlı ekstra ücret (faturaya eklenir) ya da ekstra gider (bizim maliyetimiz). */
class AgreementExtra extends Model
{
    use HasFactory;

    public const KINDS = ['charge' => 'Ekstra ücret', 'cost' => 'Ekstra gider'];

    protected $fillable = ['agreement_id', 'label', 'kind', 'amount', 'recurring'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'recurring' => 'boolean'];
    }

    public function agreement(): BelongsTo
    {
        return $this->belongsTo(Agreement::class);
    }

    public function getKindLabelAttribute(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }
}
