<?php

namespace App\Http\Requests;

use App\Models\SalaryPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SalaryPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $money = fn ($v) => $v === null || $v === '' ? 0 : str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $v));

        $this->merge([
            'base_salary' => $money($this->base_salary),
            'bonus' => $money($this->bonus),
            'deduction' => $money($this->deduction),
            'advance' => $money($this->advance),
            'paid_amount' => $money($this->paid_amount),
            'refund_amount' => $money($this->refund_amount),
        ]);
    }

    public function rules(): array
    {
        return [
            'base_salary' => ['required', 'numeric', 'min:0'],
            'bonus' => ['nullable', 'numeric', 'min:0'],
            'deduction' => ['nullable', 'numeric', 'min:0'],
            'advance' => ['nullable', 'numeric', 'min:0'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', Rule::in(array_keys(SalaryPayment::METHODS))],
            'paid_at' => ['nullable', 'date'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'base_salary' => 'maaş', 'bonus' => 'prim', 'deduction' => 'kesinti', 'advance' => 'avans',
            'paid_amount' => 'ödenen tutar', 'payment_method' => 'ödeme yöntemi', 'paid_at' => 'ödeme tarihi', 'note' => 'açıklama', 'refund_amount' => 'iade alınan', 'refund_at' => 'iade tarihi',
        ];
    }
}
