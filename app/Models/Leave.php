<?php

namespace App\Models;

use App\Models\Concerns\HasNotes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Leave extends Model
{
    use HasFactory, HasNotes;

    /** İzin türleri Ayarlar > İzin Türleri'nden: slug => [label, deduct, icon] */
    public static function types(): array
    {
        return ListItem::of('leave_type')->mapWithKeys(fn ($i) => [$i['slug'] => [
            'label' => $i['label'], 'deduct' => (bool) ($i['meta']['deduct'] ?? false), 'icon' => $i['meta']['icon'] ?? 'fa-calendar',
        ]])->all();
    }

    protected $fillable = [
        'employee_id', 'user_id', 'type', 'leave_year', 'start_date', 'end_date', 'return_date',
        'days', 'deduct_annual', 'requested_by', 'note',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'return_date' => 'date',
            'days' => 'decimal:1',
            'deduct_annual' => 'boolean',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class)->withTrashed();
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopeForYear(Builder $q, int $year): Builder
    {
        return $q->where('leave_year', $year);
    }

    /** Verilen günde izinde olanlar. */
    public function scopeOnDate(Builder $q, Carbon $date): Builder
    {
        return $q->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::types()[$this->type]['label'] ?? $this->type;
    }

    /** İki tarih arasındaki iş günü sayısı (Cumartesi-Pazar hariç). */
    public static function businessDays(Carbon $start, Carbon $end): int
    {
        if ($end->lt($start)) {
            return 0;
        }
        $n = 0;
        for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
            if (! $d->isWeekend()) {
                $n++;
            }
        }

        return $n;
    }

    /** İzin bitişinden sonraki ilk iş günü. */
    public static function nextWorkday(Carbon $end): Carbon
    {
        $d = $end->copy()->addDay();
        while ($d->isWeekend()) {
            $d->addDay();
        }

        return $d;
    }
}
