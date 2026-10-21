<?php

namespace App\Http\Requests;

use App\Models\Agreement;
use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $num = fn ($v) => $v === null || trim((string) $v) === ''
            ? null
            : str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $v));

        $this->merge([
            'm2' => $num($this->m2),
            'm2_rate' => $num($this->m2_rate),
            'amount' => $num($this->amount) ?? 0,
            'vat_rate' => $num($this->vat_rate) ?? Setting::amount('vat_rate'),
            'payment_day' => $this->payment_day ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(Agreement::TYPES))],
            'status' => ['required', Rule::in(array_keys(Agreement::STATUSES))],
            'contact_name' => ['nullable', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'm2' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'm2_rate' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'vat_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'payment_day' => ['nullable', 'integer', 'between:1,31'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'kurum adı', 'type' => 'tür', 'status' => 'durum', 'contact_name' => 'yetkili',
            'phone' => 'telefon', 'email' => 'e-posta', 'address' => 'konum', 'm2' => 'alan',
            'm2_rate' => 'm² fiyatı', 'amount' => 'tutar', 'vat_rate' => 'KDV oranı',
            'start_date' => 'başlangıç', 'end_date' => 'bitiş', 'payment_day' => 'ödeme günü', 'note' => 'açıklama',
        ];
    }
}
