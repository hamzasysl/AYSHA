<?php

namespace App\Models;

use App\Models\Concerns\HasNotes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Temizlik hizmeti verilen kurumla yapılan anlaşma (sürekli ya da tek seferlik). */
class Agreement extends Model
{
    use HasFactory, HasNotes;

    public const TYPES = ['recurring' => 'Sürekli', 'one_time' => 'Tek seferlik'];

    public const STATUSES = ['active' => 'Aktif', 'passive' => 'Pasif'];

    protected $fillable = [
        'name', 'type', 'status', 'contact_name', 'phone', 'email', 'address',
        'm2', 'm2_rate', 'amount', 'vat_rate', 'start_date', 'end_date', 'payment_day', 'note',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date', 'end_date' => 'date',
            'm2' => 'decimal:2', 'm2_rate' => 'decimal:2', 'amount' => 'decimal:2', 'vat_rate' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Agreement $a) {
            // m² ve m² fiyatı girilmişse ana tutar oradan hesaplanır
            if ($a->m2 > 0 && $a->m2_rate > 0) {
                $a->amount = round((float) $a->m2 * (float) $a->m2_rate, 2);
            }
        });

        static::deleting(fn (Agreement $a) => $a->deleteNotes());
    }

    public function extras(): HasMany
    {
        return $this->hasMany(AgreementExtra::class)->orderBy('kind')->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(AgreementPayment::class)->orderByDesc('period_year')->orderByDesc('period_month')->orderByDesc('id');
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('status', 'active');
    }

    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Sürekli anlaşmalarda her ay tekrar eden ekstralar; tek seferliklerde hepsi. */
    private function countedExtras(string $kind)
    {
        $extras = $this->relationLoaded('extras') ? $this->extras : $this->extras()->get();

        return $extras->where('kind', $kind)->when($this->type === 'recurring', fn ($c) => $c->where('recurring', true));
    }

    /** Faturaya eklenen ekstra ücretler (KDV hariç). */
    public function getExtraChargesAttribute(): float
    {
        return round((float) $this->countedExtras('charge')->sum('amount'), 2);
    }

    /** Bize maliyet olan ekstra giderler. */
    public function getExtraCostsAttribute(): float
    {
        return round((float) $this->countedExtras('cost')->sum('amount'), 2);
    }

    /** KDV hariç toplam (ana tutar + ekstra ücretler). */
    public function getNetTotalAttribute(): float
    {
        return round((float) $this->amount + $this->extra_charges, 2);
    }

    public function getVatAmountAttribute(): float
    {
        return round($this->net_total * (float) $this->vat_rate / 100, 2);
    }

    /** KDV dahil toplam — sürekli anlaşmalarda aylık tutar. */
    public function getGrossTotalAttribute(): float
    {
        return round($this->net_total + $this->vat_amount, 2);
    }

    /** Ekstra giderler düşülünce kalan (KDV hariç). */
    public function getProfitAttribute(): float
    {
        return round($this->net_total - $this->extra_costs, 2);
    }

    public function paymentFor(int $year, ?int $month): ?AgreementPayment
    {
        $payments = $this->relationLoaded('payments') ? $this->payments : $this->payments()->get();

        return $payments->first(fn ($p) => (int) $p->period_year === $year && (int) $p->period_month === $month);
    }
}
