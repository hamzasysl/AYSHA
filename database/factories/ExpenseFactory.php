<?php

namespace Database\Factories;

use App\Models\Employee;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Factories\Factory;

class ExpenseFactory extends Factory
{
    protected $model = Expense::class;

    public function definition(): array
    {
        return [
            'employee_id' => Employee::factory(),
            'category' => 'meal',
            'expense_date' => now()->toDateString(),
            'description' => 'Yemek ücreti',
            'amount' => 2500,
            'status' => 'pending',
        ];
    }
}
