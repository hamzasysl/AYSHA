<?php

namespace App\Http\Requests;

use App\Models\Expense;
use App\Models\SalaryPayment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->amount !== null ? str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $this->amount)) : null,
            'employee_id' => $this->employee_id ?: null,
            'employee_ids' => array_values(array_filter((array) $this->employee_ids)),
            'paid_amount' => $this->paid_amount !== null && $this->paid_amount !== '' ? str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $this->paid_amount)) : null,
            'deduction' => $this->deduction !== null && $this->deduction !== '' ? str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $this->deduction)) : 0,
            'refund_amount' => $this->refund_amount !== null && $this->refund_amount !== '' ? str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $this->refund_amount)) : 0,
            'payment_method' => $this->payment_method ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['nullable', 'exists:employees,id'],
            'employee_ids' => ['nullable', 'array'],
            'employee_ids.*' => ['integer', 'exists:employees,id'],
            'category' => ['required', Rule::in(array_keys(Expense::categories()))],
            'expense_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999'],
            'deduction' => ['nullable', 'numeric', 'min:0', 'lte:amount'],
            'deduction_note' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(array_keys(Expense::STATUSES))],
            'payment_method' => ['nullable', Rule::in(array_keys(SalaryPayment::METHODS))],
            'paid_at' => ['nullable', 'date'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'personel', 'employee_ids' => 'personel', 'category' => 'kategori', 'expense_date' => 'tarih', 'description' => 'açıklama',
            'amount' => 'tutar', 'deduction' => 'kesinti', 'deduction_note' => 'kesinti açıklaması', 'status' => 'durum', 'payment_method' => 'ödeme yöntemi', 'paid_at' => 'ödeme tarihi', 'note' => 'not', 'paid_amount' => 'ödenen tutar', 'refund_amount' => 'iade alınan', 'refund_at' => 'iade tarihi',
        ];
    }
}
