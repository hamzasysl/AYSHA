<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PerformanceReview extends Model
{
    use HasFactory;

    public const CRITERIA = [
        'attendance' => 'Devam / Dakiklik',
        'quality' => 'İş Kalitesi',
        'attitude' => 'Tutum / İletişim',
    ];

    protected $fillable = [
        'employee_id', 'user_id', 'review_date', 'period_year', 'period_month', 'attendance', 'quality', 'attitude',
        'score', 'strengths', 'improvements', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'review_date' => 'date',
        ];
    }

    public function scopeForPeriod(\Illuminate\Database\Eloquent\Builder $q, int $year, int $month): \Illuminate\Database\Eloquent\Builder
    {
        return $q->where('period_year', $year)->where('period_month', $month);
    }

    /** "Ağustos 2026" — değerlendirilen ay */
    public function getPeriodLabelAttribute(): string
    {
        return (SalaryPayment::MONTHS[$this->period_month] ?? $this->period_month).' '.$this->period_year;
    }

    /** Varsayılan değerlendirme dönemi: içinde bulunulan ayın bir öncesi. */
    public static function defaultPeriod(): array
    {
        $d = now()->startOfMonth()->subMonth();

        return [$d->year, $d->month];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /** 1-10 puanı renk sınıfına çevirir (badge için). */
    public function getScoreColorAttribute(): string
    {
        return match (true) {
            $this->score >= 8 => 'emerald',
            $this->score >= 6 => 'amber',
            default => 'red',
        };
    }
}
