<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\PerformanceReview;
use Illuminate\Database\Eloquent\Factories\Factory;

class PerformanceReviewFactory extends Factory
{
    protected $model = PerformanceReview::class;

    public function definition(): array
    {
        $attendance = fake()->numberBetween(2, 5);
        $quality = fake()->numberBetween(2, 5);
        $attitude = fake()->numberBetween(2, 5);
        $score = (int) round(($attendance + $quality + $attitude) / 15 * 10);

        $reviewDate = \Illuminate\Support\Carbon::instance(fake()->dateTimeBetween('-5 months', 'now'));
        $period = $reviewDate->copy()->startOfMonth()->subMonth();

        return [
            'employee_id' => Employee::factory(),
            'review_date' => $reviewDate->format('Y-m-d'),
            'period_year' => $period->year,
            'period_month' => $period->month,
            'attendance' => $attendance,
            'quality' => $quality,
            'attitude' => $attitude,
            'score' => max(1, min(10, $score)),
            'strengths' => fake()->randomElement(['Düzenli ve titiz çalışıyor.', 'Müşteri ile iletişimi çok iyi.', 'Ekip içinde uyumlu.', 'Zamanında geliyor, işini aksatmıyor.', null]),
            'improvements' => fake()->randomElement(['Kimyasal kullanımında daha dikkatli olmalı.', 'Ekipman temizliğine özen göstermeli.', 'Vardiya değişimlerinde haber vermeli.', null]),
            'notes' => null,
        ];
    }
}
