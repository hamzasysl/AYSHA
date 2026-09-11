<?php

namespace App\Http\Requests;

use App\Models\Leave;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'deduct_annual' => $this->boolean('deduct_annual'),
            'force' => $this->boolean('force'),
            'days' => $this->days !== null && $this->days !== '' ? str_replace(',', '.', (string) $this->days) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', Rule::in(array_keys(Leave::types()))],
            'leave_year' => ['required', 'integer', 'between:2020,2100'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'return_date' => ['nullable', 'date', 'after:end_date'],
            'days' => ['required', 'numeric', 'min:0.5', 'max:365'],
            'deduct_annual' => ['boolean'],
            'requested_by' => ['nullable', 'string', 'max:150'],
            'note' => ['nullable', 'string', 'max:2000'],
            'force' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'personel', 'type' => 'izin nedeni', 'leave_year' => 'iznin ait olduğu yıl', 'start_date' => 'izne çıkış tarihi',
            'end_date' => 'izin bitiş tarihi', 'return_date' => 'iş başı tarihi', 'days' => 'izin süresi (iş günü)', 'requested_by' => 'izni talep eden', 'note' => 'not',
        ];
    }
}
