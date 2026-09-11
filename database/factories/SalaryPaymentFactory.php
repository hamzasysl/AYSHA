<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\SalaryPayment;
use Illuminate\Database\Eloquent\Factories\Factory;

class SalaryPaymentFactory extends Factory
{
    protected $model = SalaryPayment::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'period_year' => now()->year,
            'period_month' => now()->month,
            'base_salary' => 25000,
            'bonus' => 0,
            'deduction' => 0,
            'advance' => 0,
            'paid_amount' => 0,
        ];
    }
}
