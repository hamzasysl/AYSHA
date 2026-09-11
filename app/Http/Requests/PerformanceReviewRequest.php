<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PerformanceReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'review_date' => ['required', 'date'],
            'period_year' => ['required', 'integer', 'between:2020,2100'],
            'period_month' => ['required', 'integer', 'between:1,12',
                Rule::unique('performance_reviews', 'period_month')
                    ->where('employee_id', $this->employee_id)
                    ->where('period_year', $this->period_year)
                    ->ignore($this->route('review')?->id),
            ],
            'attendance' => ['required', 'integer', 'between:1,5'],
            'quality' => ['required', 'integer', 'between:1,5'],
            'attitude' => ['required', 'integer', 'between:1,5'],
            'score' => ['required', 'integer', 'between:1,10'],
            'strengths' => ['nullable', 'string', 'max:2000'],
            'improvements' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return ['period_month.unique' => 'Bu personel için seçilen ay zaten değerlendirilmiş. Var olan kaydı düzenleyin.'];
    }

    public function attributes(): array
    {
        return [
            'employee_id' => 'personel', 'review_date' => 'değerlendirme tarihi', 'period_year' => 'dönem yılı', 'period_month' => 'dönem', 'attendance' => 'devam',
            'quality' => 'iş kalitesi', 'attitude' => 'tutum', 'score' => 'genel puan',
            'strengths' => 'güçlü yönler', 'improvements' => 'gelişim alanları', 'notes' => 'notlar',
        ];
    }
}
