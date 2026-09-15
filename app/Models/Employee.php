<?php

namespace App\Models;

use App\Models\Concerns\HasNotes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use HasFactory, HasNotes, SoftDeletes;

    public const STATUSES = [
        'active' => 'Aktif',
        'passive' => 'Pasif',
    ];

    /** Görev seçenekleri Ayarlar > Görevler'den. */
    public static function positions(): array
    {
        return ListItem::labels('position');
    }

    protected static function booted(): void
    {
        // Kalıcı silmede notlar ve değerlendirmeler de gitsin (sahipsiz kayıt panoda 404 veriyor).
        static::forceDeleted(function (Employee $e) {
            $e->deleteNotes();
            $e->performanceReviews()->delete();
        });
    }

    protected $fillable = [
        'first_name', 'last_name', 'tc_no', 'phone', 'email', 'position',
        'hire_date', 'termination_date', 'birth_date', 'status', 'salary',
        'is_retired', 'meal_allowance', 'travel_allowance', 'annual_leave_days',
        'bank_name', 'bank_code', 'branch_code', 'account_no', 'iban', 'account_holder', 'address', 'emergency_contact',
    ];

    protected function casts(): array
    {
        return [
            'hire_date' => 'date',
            'termination_date' => 'date',
            'birth_date' => 'date',
            'salary' => 'decimal:2',
            'is_retired' => 'boolean',
            'meal_allowance' => 'decimal:2',
            'travel_allowance' => 'decimal:2',
        ];
    }

    // ---- İlişkiler ----

    public function salaryPayments(): HasMany
    {
        return $this->hasMany(SalaryPayment::class)->orderByDesc('period_year')->orderByDesc('period_month');
    }

    public function performanceReviews(): HasMany
    {
        return $this->hasMany(PerformanceReview::class)->orderByDesc('review_date');
    }

    public function leaves(): HasMany
    {
        return $this->hasMany(Leave::class)->orderByDesc('start_date');
    }

    /** Yıllık izin durumu: hak, kullanılan (yıllık izinden düşülenler), kalan. */
    public function leaveBalance(int $year): array
    {
        $used = (float) $this->leaves()->where('leave_year', $year)->where('deduct_annual', true)->sum('days');
        $entitlement = (float) ($this->annual_leave_days ?? 14);

        return ['entitlement' => $entitlement, 'used' => $used, 'remaining' => round($entitlement - $used, 1)];
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class)->orderByDesc('expense_date');
    }

    // ---- Scope'lar ----

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (! $term) {
            return $query;
        }

        $words = preg_split('/\s+/', trim($term), -1, PREG_SPLIT_NO_EMPTY);

        // Her kelime ad, soyad, telefon, TC veya görevden birinde geçmeli ("ayşe yılmaz" gibi aramalar için).
        foreach ($words as $word) {
            $query->where(function (Builder $q) use ($word) {
                $q->where('first_name', 'like', "%{$word}%")
                    ->orWhere('last_name', 'like', "%{$word}%")
                    ->orWhere('phone', 'like', "%{$word}%")
                    ->orWhere('tc_no', 'like', "%{$word}%")
                    ->orWhere('position', 'like', "%{$word}%");
            });
        }

        return $query;
    }

    // ---- Accessor'lar ----

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function getInitialsAttribute(): string
    {
        return mb_strtoupper(mb_substr($this->first_name, 0, 1).mb_substr($this->last_name, 0, 1));
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /** Bu personel için geçerli aylık yemek ücreti (özel değer yoksa ayarlardaki emekli/standart varsayılan). */
    public function getEffectiveMealAllowanceAttribute(): float
    {
        return $this->meal_allowance !== null
            ? (float) $this->meal_allowance
            : Setting::amount($this->is_retired ? 'meal_allowance_retired' : 'meal_allowance');
    }

    public function getEffectiveTravelAllowanceAttribute(): float
    {
        return $this->travel_allowance !== null
            ? (float) $this->travel_allowance
            : Setting::amount($this->is_retired ? 'travel_allowance_retired' : 'travel_allowance');
    }

    public function getFormattedIbanAttribute(): ?string
    {
        if (! $this->iban) {
            return null;
        }

        return trim(chunk_split(preg_replace('/\s+/', '', $this->iban), 4, ' '));
    }

    public function getAverageScoreAttribute(): ?float
    {
        $avg = $this->performanceReviews()->avg('score');

        return $avg !== null ? round((float) $avg, 1) : null;
    }

    public function setIbanAttribute(?string $value): void
    {
        $this->attributes['iban'] = $value ? mb_strtoupper(preg_replace('/\s+/', '', $value)) : null;
    }
}
