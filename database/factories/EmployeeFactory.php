<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    private const BANKS = ['Ziraat Bankası', 'İş Bankası', 'Garanti BBVA', 'Yapı Kredi', 'Akbank', 'Halkbank', 'VakıfBank', 'QNB', 'DenizBank'];

    public function definition(): array
    {
        $first = fake()->firstName();
        $last = fake()->lastName();

        return [
            'first_name' => $first,
            'last_name' => $last,
            'tc_no' => (string) fake()->unique()->numerify('#########0#'),
            'phone' => '05'.fake()->numerify('## ### ## ##'),
            'email' => null,
            'position' => 'Temizlik Personeli',
            'hire_date' => fake()->dateTimeBetween('-4 years', '-1 month')->format('Y-m-d'),
            'birth_date' => fake()->dateTimeBetween('-58 years', '-20 years')->format('Y-m-d'),
            'status' => 'active',
            'is_retired' => fake()->boolean(20),
            'bank_code' => '62',
            'branch_code' => (string) fake()->randomElement([544, 721, 394, 1012]),
            'account_no' => (string) fake()->numerify('68#####'),
            'salary' => fake()->randomElement([22000, 24000, 25000, 26500, 28000, 30000, 32000]),
            'bank_name' => fake()->randomElement(self::BANKS),
            'iban' => 'TR'.fake()->numerify('########################'),
            'account_holder' => $first.' '.$last,
            'address' => fake()->address(),
            'emergency_contact' => fake()->firstName().' '.fake()->lastName().' - 05'.fake()->numerify('## ### ## ##'),
        ];
    }

    public function passive(): static
    {
        return $this->state(fn () => [
            'status' => 'passive',
            'termination_date' => fake()->dateTimeBetween('-6 months', '-1 week')->format('Y-m-d'),
        ]);
    }
}
