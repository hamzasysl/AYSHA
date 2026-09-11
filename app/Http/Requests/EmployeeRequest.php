<?php

namespace App\Http\Requests;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'iban' => $this->iban ? mb_strtoupper(preg_replace('/\s+/', '', $this->iban)) : null,
            'tc_no' => $this->tc_no ? preg_replace('/\D/', '', $this->tc_no) : null,
            'salary' => $this->salary !== null ? str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $this->salary)) : null,
            'meal_allowance' => $this->allowanceOrNull($this->money($this->meal_allowance), 'meal_allowance'),
            'travel_allowance' => $this->allowanceOrNull($this->money($this->travel_allowance), 'travel_allowance'),
            'is_retired' => $this->boolean('is_retired'),
            'phone' => \App\Support\Phone::format($this->phone),
            'bank_code' => $this->bank_code !== null ? trim((string) $this->bank_code) : null,
            'branch_code' => $this->branch_code !== null ? trim((string) $this->branch_code) : null,
            'account_no' => $this->account_no !== null ? preg_replace('/\s+/', '', (string) $this->account_no) : null,
        ]);
    }

    /** Girilen tutar geçerli standart/emekli varsayılana eşitse null sakla; böylece Ayarlar değişince personel de güncellenir. */
    private function allowanceOrNull(?string $value, string $key): ?string
    {
        if ($value === null) {
            return null;
        }
        $default = \App\Models\Setting::amount($this->boolean('is_retired') ? $key.'_retired' : $key);

        return abs((float) $value - $default) < 0.005 ? null : $value;
    }

    /** "7.800,00" -> 7800.00; boş -> null (varsayılan kullanılsın) */
    private function money(mixed $v): ?string
    {
        if ($v === null || trim((string) $v) === '') {
            return null;
        }

        return str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $v));
    }

    public function rules(): array
    {
        $id = $this->route('employee')?->id;

        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'tc_no' => ['nullable', 'digits:11', Rule::unique('employees', 'tc_no')->ignore($id)->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'position' => ['required', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
            'termination_date' => ['nullable', 'date', 'after_or_equal:hire_date'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'status' => ['required', Rule::in(array_keys(Employee::STATUSES))],
            'salary' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'is_retired' => ['boolean'],
            'annual_leave_days' => ['nullable', 'integer', 'between:0,365'],
            'meal_allowance' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'travel_allowance' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'bank_name' => ['nullable', 'string', 'max:100'],
            'bank_code' => ['nullable', 'string', 'max:10'],
            'branch_code' => ['nullable', 'string', 'max:10'],
            'account_no' => ['nullable', 'string', 'max:30'],
            'iban' => ['nullable', 'string', 'regex:/^TR\d{24}$/'],
            'account_holder' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:1000'],
            'emergency_contact' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'first_name' => 'ad', 'last_name' => 'soyad', 'tc_no' => 'TC kimlik no', 'phone' => 'telefon',
            'email' => 'e-posta', 'position' => 'görev', 'hire_date' => 'işe giriş tarihi',
            'termination_date' => 'işten ayrılış tarihi', 'birth_date' => 'doğum tarihi', 'status' => 'durum',
            'salary' => 'maaş', 'bank_name' => 'banka', 'is_retired' => 'emekli', 'annual_leave_days' => 'yıllık izin hakkı', 'meal_allowance' => 'yemek ücreti', 'travel_allowance' => 'yol ücreti',
            'bank_code' => 'banka kodu', 'branch_code' => 'şube kodu', 'account_no' => 'hesap no', 'iban' => 'IBAN', 'account_holder' => 'hesap sahibi',
            'address' => 'adres', 'emergency_contact' => 'acil durum kişisi',
        ];
    }

    public function messages(): array
    {
        return [
            'iban.regex' => 'IBAN "TR" ile başlamalı ve toplam 26 karakter olmalıdır.',
        ];
    }
}
