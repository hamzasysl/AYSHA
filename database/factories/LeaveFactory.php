<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Leave;
use Illuminate\Database\Eloquent\Factories\Factory;

class LeaveFactory extends Factory
{
    protected $model = Leave::class;

    public function definition(): array
    {
        $start = \Illuminate\Support\Carbon::instance(fake()->dateTimeBetween('-3 months', 'now'))->startOfDay();
        while ($start->isWeekend()) {
            $start->addDay();
        }
        $end = $start->copy()->addDays(fake()->numberBetween(0, 4));

        return [
            'employee_id' => Employee::factory(),
            'type' => 'annual',
            'leave_year' => $start->year,
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'return_date' => Leave::nextWorkday($end)->toDateString(),
            'days' => Leave::businessDays($start, $end),
            'deduct_annual' => true,
        ];
    }
}
