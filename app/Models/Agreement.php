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

    public function entries(): HasMany
    {
        return $this->hasMany(AgreementEntry::class)->orderByDesc('entry_date')->orderByDesc('id');
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

    /** Aylık (ya da tek seferlik) anlaşma bedeli, KDV hariç. */
    public function getNetTotalAttribute(): float
    {
        return round((float) $this->amount, 2);
    }

    public function getVatAmountAttribute(): float
    {
        return round($this->net_total * (float) $this->vat_rate / 100, 2);
    }

    /** KDV dahil anlaşma bedeli. */
    public function getGrossTotalAttribute(): float
    {
        return round($this->net_total + $this->vat_amount, 2);
    }

    private function entriesOf(string $kind, ?int $year = null, ?int $month = null): float
    {
        $entries = $this->relationLoaded('entries') ? $this->entries : $this->entries()->get();

        return round((float) $entries
            ->where('kind', $kind)
            ->when($year, fn ($c) => $c->filter(fn ($e) => (int) $e->entry_date->year === $year))
            ->when($month, fn ($c) => $c->filter(fn ($e) => (int) $e->entry_date->month === $month))
            ->sum(fn ($e) => (float) $e->amount), 2);
    }

    /** Alınan ödemeler; dönem verilirse o aya ait olanlar. */
    public function paid(?int $year = null, ?int $month = null): float
    {
        return $this->entriesOf('payment', $year, $month);
    }

    public function extraIncome(?int $year = null, ?int $month = null): float
    {
        return $this->entriesOf('income', $year, $month);
    }

    public function extraExpense(?int $year = null, ?int $month = null): float
    {
        return $this->entriesOf('expense', $year, $month);
    }

    /** Alınan ödeme + ekstra gelir − ekstra gider. */
    public function balance(?int $year = null, ?int $month = null): float
    {
        return round($this->paid($year, $month) + $this->extraIncome($year, $month) - $this->extraExpense($year, $month), 2);
    }
}
