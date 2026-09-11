<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Expense;
use App\Models\PerformanceReview;
use App\Models\SalaryPayment;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 20 kişilik temizlik ekibi, son 3 ayın bordrosu ve performans kayıtları.
 * Çalıştır: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);
        $admin = User::first();

        $employees = Employee::factory()->count(18)->create()
            ->merge(Employee::factory()->count(2)->passive()->create());

        // Son 3 ay bordro: 2 ay önce tamamen ödendi, geçen ay çoğu ödendi, bu ay bekliyor.
        foreach ([2, 1, 0] as $back) {
            $period = now()->startOfMonth()->subMonths($back);

            foreach ($employees->where('status', 'active') as $employee) {
                $bonus = fake()->boolean(25) ? fake()->randomElement([500, 1000, 1500, 2000]) : 0;
                $advance = fake()->boolean(15) ? fake()->randomElement([1000, 2000, 3000]) : 0;
                $deduction = fake()->boolean(10) ? fake()->randomElement([250, 500, 750]) : 0;
                $net = $employee->salary + $bonus - $advance - $deduction;

                $paid = match ($back) {
                    2 => fake()->boolean(15) ? $net + fake()->randomElement([15, 50, 100]) : $net, // elden yuvarlama: fazla ödeme

                    1 => fake()->boolean(85) ? (fake()->boolean(10) ? $net + 20 : $net) : (fake()->boolean() ? round($net / 2) : 0),
                    default => fake()->boolean(20) ? $net : 0,
                };

                SalaryPayment::create([
                    'employee_id' => $employee->id,
                    'period_year' => $period->year,
                    'period_month' => $period->month,
                    'base_salary' => $employee->salary,
                    'bonus' => $bonus,
                    'advance' => $advance,
                    'deduction' => $deduction,
                    'paid_amount' => $paid,
                    'payment_method' => $paid > 0 ? fake()->randomElement(['transfer', 'transfer', 'cash']) : null,
                    'paid_at' => $paid > 0 ? $period->copy()->addMonth()->day(fake()->numberBetween(1, 7))->min(now())->toDateString() : null,
                ]);
            }
        }

        foreach ($employees as $employee) {
            foreach (array_slice([3, 2, 1], 0, fake()->numberBetween(1, 3)) as $back) {
                $period = now()->startOfMonth()->subMonths($back);
                PerformanceReview::factory()->create([
                    'employee_id' => $employee->id,
                    'user_id' => $admin?->id,
                    'period_year' => $period->year,
                    'period_month' => $period->month,
                    'review_date' => $period->copy()->addMonth()->day(fake()->numberBetween(1, 5))->toDateString(),
                ]);
            }
        }

        // Giderler: son 3 ay yemek + yol (kişi başı), aylık temizlik malzemesi, birkaç diğer
        foreach ([2, 1, 0] as $back) {
            $period = now()->startOfMonth()->subMonths($back);
            $paidByDefault = $back > 0;

            foreach ($employees->where('status', 'active') as $employee) {
                $mealAmount = fake()->randomElement([2200, 2500, 3000]);
                $mealPaid = $paidByDefault || fake()->boolean(30);
                Expense::create(['employee_id' => $employee->id, 'category' => 'meal', 'expense_date' => $period->copy()->day(fake()->numberBetween(1, 5)),
                    'description' => $period->translatedFormat('F').' yemek ücreti', 'amount' => $mealAmount,
                    'status' => $mealPaid ? 'paid' : 'pending', 'payment_method' => $mealPaid ? fake()->randomElement(['transfer', 'cash']) : null,
                    'paid_amount' => $mealPaid && fake()->boolean(20) ? $mealAmount + 50 : null]);
                Expense::create(['employee_id' => $employee->id, 'category' => 'travel', 'expense_date' => $period->copy()->day(fake()->numberBetween(1, 5)),
                    'description' => $period->translatedFormat('F').' yol parası', 'amount' => fake()->randomElement([1200, 1500, 1800]),
                    'status' => $paidByDefault || fake()->boolean(30) ? 'paid' : 'pending', 'payment_method' => fake()->randomElement(['transfer', 'cash'])]);
            }

            foreach (['Deterjan ve kimyasal alımı' => 8500, 'Mop, bez, eldiven' => 2400, 'Çöp poşeti (toptan)' => 1300] as $desc => $amt) {
                $ex = Expense::create(['employee_id' => fake()->boolean(60) ? $employees->where('status', 'active')->random()->id : null, 'category' => 'cleaning',
                    'expense_date' => $period->copy()->day(fake()->numberBetween(3, 25)), 'description' => $desc, 'amount' => $amt + fake()->numberBetween(-300, 300),
                    'status' => $paidByDefault ? 'paid' : fake()->randomElement(['paid', 'pending']), 'payment_method' => 'transfer']);
                if (fake()->boolean(50)) {
                    $ex->notes()->create(['user_id' => $admin?->id, 'content' => fake()->randomElement(['Fatura dosyaya eklendi.', 'Fiyat geçen aya göre arttı, alternatif tedarikçi bakılacak.', 'Fatura bekleniyor.'])]);
                }
            }
            Expense::create(['employee_id' => null, 'category' => 'other', 'expense_date' => $period->copy()->day(15), 'description' => 'Araç yakıt', 'amount' => fake()->numberBetween(3000, 5000), 'status' => 'paid', 'payment_method' => 'cash']);
        }

        $employees->random(6)->each(function (Employee $e) use ($admin) {
            $e->notes()->create([
                'user_id' => $admin?->id,
                'content' => fake()->randomElement([
                    'Ekim ayı için izin talebi var, planlamaya dahil et.',
                    'Yeni üniforma teslim edildi.',
                    'Müşteri (Plaza B) teşekkür etti, prim değerlendirilebilir.',
                    'SGK giriş evrakları tamamlandı.',
                    'Sağlık raporu 3 gün, dosyaya eklendi.',
                ]),
            ]);
        });
    }
}
