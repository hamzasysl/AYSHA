<?php

namespace App\Http\Controllers;

use App\Http\Requests\SalaryPaymentRequest;
use App\Models\Employee;
use App\Models\SalaryPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Support\GarantiPayrollFile;
use Illuminate\Support\Facades\DB;

class SalaryPaymentController extends Controller
{
    /** Maaş ödemeleri artık Muhasebe sayfasının "Maaşlar" sekmesinde. */
    public function index(Request $request)
    {
        [$year, $month] = $this->period($request);

        return redirect()->route('expenses.index', array_filter(['category' => 'salary', 'year' => $year, 'month' => $month, 'status' => $request->get('status')]));
    }

    /** Seçilen dönem için tüm aktif personele bekleyen maaş kaydı açar (var olanlara dokunmaz). */
    public function generate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2020,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $created = 0;

        DB::transaction(function () use ($data, &$created) {
            $existing = SalaryPayment::forPeriod($data['year'], $data['month'])->pluck('employee_id')->all();

            Employee::active()->whereNotIn('id', $existing)->each(function (Employee $employee) use ($data, &$created) {
                SalaryPayment::create([
                    'employee_id' => $employee->id,
                    'period_year' => $data['year'],
                    'period_month' => $data['month'],
                    'base_salary' => $employee->salary,
                ]);
                $created++;
            });
        });

        $label = SalaryPayment::MONTHS[(int) $data['month']].' '.$data['year'];

        return redirect()->route('expenses.index', ['category' => 'salary', 'year' => $data['year'], 'month' => $data['month']])
            ->with('success', $created > 0
                ? "{$label} dönemi için {$created} personele maaş kaydı oluşturuldu."
                : "{$label} döneminde eklenecek yeni personel yok.");
    }

    public function update(SalaryPaymentRequest $request, SalaryPayment $payment): RedirectResponse
    {
        $payment->fill($request->validated());

        if ((float) $payment->paid_amount > 0 && ! $payment->payment_method) {
            $payment->payment_method = 'transfer';
        }

        $payment->save();

        return back()->with('success', $payment->employee->full_name.' için ödeme kaydı güncellendi.');
    }

    /** Tek tıkla "tamamı ödendi" işaretle. */
    public function markPaid(Request $request, SalaryPayment $payment): RedirectResponse
    {
        $data = $request->validate([
            'payment_method' => ['nullable', 'in:transfer,cash'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $payment->paid_amount = $payment->net_amount;
        $payment->payment_method = $data['payment_method'] ?? $payment->payment_method ?? 'transfer';
        $payment->paid_at = $data['paid_at'] ?? now()->toDateString();
        $payment->save();

        return back()->with('success', $payment->employee->full_name.' maaşı ödendi olarak işaretlendi.');
    }

    /** Durum açılır menüsü: bekliyor <-> ödendi (kısmi sadece düzenleme ile). */
    public function setStatus(Request $request, SalaryPayment $payment): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:pending,paid']]);

        if ($data['status'] === 'paid') {
            $payment->paid_amount = max((float) $payment->paid_amount, (float) $payment->net_amount);
            $payment->payment_method = $payment->payment_method ?? 'transfer';
            $payment->paid_at = $payment->paid_at ?? now()->toDateString();
        } else {
            $payment->paid_amount = 0;
            $payment->payment_method = null;
        }
        $payment->save();

        return back()->with('success', $payment->employee->full_name.' · '.$payment->period_label.' → '.$payment->status_label);
    }

    /** Dönemdeki tüm bekleyenleri ödendi işaretle. */
    public function markAllPaid(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer'],
            'month' => ['required', 'integer', 'between:1,12'],
            'payment_method' => ['nullable', 'in:transfer,cash'],
        ]);

        $count = 0;
        SalaryPayment::forPeriod($data['year'], $data['month'])->where('status', '!=', 'paid')->each(function (SalaryPayment $p) use ($data, &$count) {
            $p->paid_amount = $p->net_amount;
            $p->payment_method = $data['payment_method'] ?? $p->payment_method ?? 'transfer';
            $p->paid_at = $p->paid_at ?? now()->toDateString();
            $p->save();
            $count++;
        });

        return back()->with('success', "{$count} ödeme 'Ödendi' olarak işaretlendi.");
    }

    /** Garanti "TGB Yeni Maaş Dosyası" Excel'i: dönemde kalan tutarı olan personel (kalan tutar kadar). */
    public function bankFile(Request $request)
    {
        [$year, $month] = $this->period($request);
        $scope = $request->get('scope', 'remaining'); // remaining | all
        $paymentDate = $request->get('payment_date') ? \Illuminate\Support\Carbon::parse($request->get('payment_date')) : now();

        $payments = SalaryPayment::forPeriod($year, $month)->with('employee')->get()
            ->filter(fn ($p) => $scope === 'all' ? (float) $p->net_amount > 0 : $p->remaining > 0)
            ->sortBy(fn ($p) => $p->employee->full_name)
            ->map(fn ($p) => [
                'name' => $p->employee->full_name,
                'tc' => $p->employee->tc_no,
                'bank_code' => $p->employee->bank_code ?: '62',
                'branch_code' => $p->employee->branch_code,
                'account' => $p->employee->account_no,
                'iban' => $p->employee->iban,
                'amount' => $scope === 'all' ? (float) $p->net_amount : $p->remaining,
            ])->values();

        if ($payments->isEmpty()) {
            return back()->with('error', 'Bu dönemde bankaya gönderilecek ödeme yok.');
        }

        $label = mb_strtoupper(SalaryPayment::MONTHS[$month], 'UTF-8').' '.$year.' MAAŞ';
        $wb = GarantiPayrollFile::build($payments, $label, $paymentDate);

        return GarantiPayrollFile::download($wb, sprintf('%d-%02d-garanti-maas-dosyasi.xlsx', $year, $month));
    }

    /** Fazla ödenen tutarın personelden geri alındığını işaretle. */
    public function refundReceived(Request $request, SalaryPayment $payment): RedirectResponse
    {
        abort_if($payment->overpaid <= 0, 422, 'Bu kayıtta fazla ödeme yok.');

        $payment->refund_amount = $payment->overpaid;
        $payment->refund_at = $request->input('refund_at') ?: now()->toDateString();
        $payment->save();

        return back()->with('success', $payment->employee->full_name.' için '.number_format($payment->overpaid, 2, ',', '.').' ₺ iade alındı olarak işaretlendi.');
    }

    /** Toplu durum: seçilen bordro satırlarını ödendi / bekliyor yapar. */
    public function bulkStatus(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'status' => ['required', 'in:pending,paid'],
        ], [], ['ids' => 'kayıt']);

        $payments = SalaryPayment::whereIn('id', $data['ids'])->get();
        foreach ($payments as $payment) {
            if ($data['status'] === 'paid') {
                $payment->paid_amount = max((float) $payment->paid_amount, (float) $payment->net_amount);
                $payment->payment_method = $payment->payment_method ?? 'transfer';
                $payment->paid_at = $payment->paid_at ?? now()->toDateString();
            } else {
                $payment->paid_amount = 0;
                $payment->payment_method = null;
            }
            $payment->save();
        }

        return back()->with('success', $payments->count().' maaş kaydı "'.($data['status'] === 'paid' ? 'Ödendi' : 'Bekliyor').'" olarak işaretlendi.');
    }

    /** Toplu düzenleme: sadece doldurulan alanlar seçilen bordro satırlarına uygulanır. */
    public function bulkUpdate(Request $request): RedirectResponse
    {
        $money = fn ($v) => $v === null || trim((string) $v) === ''
            ? null
            : (float) str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $v));

        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
            'bonus' => ['nullable', 'string'],
            'advance' => ['nullable', 'string'],
            'deduction' => ['nullable', 'string'],
            'paid_amount' => ['nullable', 'string'],
            'payment_method' => ['nullable', Rule::in(array_keys(SalaryPayment::METHODS))],
            'paid_at' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['ids' => 'kayıt', 'paid_amount' => 'fiilen ödenen']);

        $values = [
            'bonus' => $money($data['bonus'] ?? null),
            'advance' => $money($data['advance'] ?? null),
            'deduction' => $money($data['deduction'] ?? null),
            'paid_amount' => $money($data['paid_amount'] ?? null),
        ];

        $payments = SalaryPayment::whereIn('id', $data['ids'])->get();
        abort_if($payments->isEmpty(), 404, 'Seçili kayıt bulunamadı.');

        foreach ($payments as $payment) {
            foreach ($values as $field => $value) {
                if ($value !== null) {
                    $payment->{$field} = $value;
                }
            }
            foreach (['payment_method', 'paid_at', 'note'] as $field) {
                if (($data[$field] ?? null) !== null && $data[$field] !== '') {
                    $payment->{$field} = $data[$field];
                }
            }
            $payment->save();
        }

        return back()->with('success', $payments->count().' maaş kaydı güncellendi.');
    }

    /** Toplu silme. */
    public function bulkDestroy(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ], [], ['ids' => 'kayıt']);

        $payments = SalaryPayment::whereIn('id', $data['ids'])->get();
        foreach ($payments as $payment) {
            $payment->delete();
        }

        return back()->with('success', $payments->count().' maaş kaydı silindi.');
    }

    public function destroy(SalaryPayment $payment): RedirectResponse
    {
        $payment->delete();

        return back()->with('success', 'Ödeme kaydı silindi.');
    }

    private function period(Request $request): array
    {
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        if ($month < 1 || $month > 12) {
            $month = (int) now()->month;
        }
        if ($year < 2020 || $year > 2100) {
            $year = (int) now()->year;
        }

        return [$year, $month];
    }
}
