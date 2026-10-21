<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgreementRequest;
use App\Models\Agreement;
use App\Models\AgreementExtra;
use App\Models\AgreementPayment;
use App\Models\SalaryPayment;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AgreementController extends Controller
{
    public function index(Request $request)
    {
        $type = in_array($request->get('type'), array_keys(Agreement::TYPES), true) ? $request->get('type') : null;
        $status = in_array($request->get('status'), array_keys(Agreement::STATUSES), true) ? $request->get('status') : null;
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);

        $all = Agreement::with(['extras', 'payments', 'notes'])->orderBy('name')->get();
        $agreements = $all
            ->when($type, fn ($c) => $c->where('type', $type))
            ->when($status, fn ($c) => $c->where('status', $status))
            ->values();

        $activeRecurring = $all->where('status', 'active')->where('type', 'recurring');
        $periodPayments = $all->flatMap->payments
            ->filter(fn ($p) => (int) $p->period_year === $year && (int) $p->period_month === $month);

        $stats = [
            'recurring' => $activeRecurring->count(),
            'one_time' => $all->where('status', 'active')->where('type', 'one_time')->count(),
            'monthly_net' => $activeRecurring->sum(fn ($a) => $a->net_total),
            'monthly_gross' => $activeRecurring->sum(fn ($a) => $a->gross_total),
            'monthly_costs' => $activeRecurring->sum(fn ($a) => $a->extra_costs),
            'collected' => $periodPayments->where('status', 'paid')->sum(fn ($p) => (float) $p->amount),
            'pending' => $periodPayments->where('status', 'pending')->sum(fn ($p) => (float) $p->amount),
        ];

        $counts = [
            'all' => $all->count(),
            'recurring' => $all->where('type', 'recurring')->count(),
            'one_time' => $all->where('type', 'one_time')->count(),
            'passive' => $all->where('status', 'passive')->count(),
        ];

        return view('agreements.index', compact('agreements', 'stats', 'counts', 'type', 'status', 'year', 'month'));
    }

    public function show(Agreement $agreement)
    {
        $agreement->load(['extras', 'payments', 'notes']);

        return view('agreements.show', compact('agreement'));
    }

    public function store(AgreementRequest $request): RedirectResponse
    {
        $agreement = Agreement::create($request->validated());

        return redirect()->route('agreements.show', $agreement)->with('success', $agreement->name.' anlaşması eklendi.');
    }

    public function update(AgreementRequest $request, Agreement $agreement): RedirectResponse
    {
        $agreement->update($request->validated());

        return back()->with('success', 'Anlaşma güncellendi.');
    }

    public function destroy(Agreement $agreement): RedirectResponse
    {
        $name = $agreement->name;
        $agreement->delete();

        return redirect()->route('agreements.index')->with('success', $name.' anlaşması silindi.');
    }

    // ---- Ekstra ücret / gider ----

    public function storeExtra(Request $request, Agreement $agreement): RedirectResponse
    {
        $data = $this->extraData($request);
        $agreement->extras()->create($data);

        return back()->with('success', $data['label'].' eklendi.');
    }

    public function updateExtra(Request $request, AgreementExtra $extra): RedirectResponse
    {
        $extra->update($this->extraData($request));

        return back()->with('success', 'Ekstra güncellendi.');
    }

    public function destroyExtra(AgreementExtra $extra): RedirectResponse
    {
        $extra->delete();

        return back()->with('success', 'Ekstra silindi.');
    }

    private function extraData(Request $request): array
    {
        $request->merge(['amount' => str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $request->input('amount')))]);

        return $request->validate([
            'label' => ['required', 'string', 'max:120'],
            'kind' => ['required', Rule::in(array_keys(AgreementExtra::KINDS))],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'recurring' => ['nullable', 'boolean'],
        ], [], ['label' => 'açıklama', 'kind' => 'tür', 'amount' => 'tutar']) + ['recurring' => $request->boolean('recurring')];
    }

    // ---- Tahsilatlar ----

    public function storePayment(Request $request, Agreement $agreement): RedirectResponse
    {
        $data = $this->paymentData($request, $agreement);

        $existing = $agreement->payments()
            ->where('period_year', $data['period_year'])
            ->where('period_month', $data['period_month'])
            ->first();

        if ($existing) {
            $existing->update($data);

            return back()->with('success', $existing->period_label.' tahsilatı güncellendi.');
        }

        $payment = $agreement->payments()->create($data);

        return back()->with('success', $payment->period_label.' tahsilatı eklendi.');
    }

    public function updatePayment(Request $request, AgreementPayment $payment): RedirectResponse
    {
        $payment->update($this->paymentData($request, $payment->agreement));

        return back()->with('success', 'Tahsilat güncellendi.');
    }

    /** Satırdaki açılır menü: bekliyor <-> tahsil edildi. */
    public function setPaymentStatus(Request $request, AgreementPayment $payment): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', Rule::in(array_keys(AgreementPayment::STATUSES))]]);
        $payment->status = $data['status'];
        $payment->save();

        return back()->with('success', $payment->agreement->name.' · '.$payment->period_label.' → '.$payment->status_label);
    }

    public function destroyPayment(AgreementPayment $payment): RedirectResponse
    {
        $payment->delete();

        return back()->with('success', 'Tahsilat silindi.');
    }

    private function paymentData(Request $request, Agreement $agreement): array
    {
        $request->merge([
            'amount' => str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $request->input('amount'))) ?: $agreement->gross_total,
            'period_month' => $request->input('period_month') ?: null,
        ]);

        return $request->validate([
            'period_year' => ['required', 'integer', 'between:2020,2100'],
            'period_month' => ['nullable', 'integer', 'between:1,12'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
            'status' => ['required', Rule::in(array_keys(AgreementPayment::STATUSES))],
            'paid_at' => ['nullable', 'date'],
            'method' => ['nullable', Rule::in(array_keys(AgreementPayment::METHODS))],
            'note' => ['nullable', 'string', 'max:255'],
        ], [], ['period_year' => 'yıl', 'period_month' => 'ay', 'amount' => 'tutar', 'status' => 'durum']);
    }

    /** Sürekli anlaşmaların seçilen ay için bekleyen tahsilat kaydını açar. */
    public function generatePayments(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'year' => ['required', 'integer', 'between:2020,2100'],
            'month' => ['required', 'integer', 'between:1,12'],
        ]);

        $created = 0;
        Agreement::active()->where('type', 'recurring')->with('extras')->each(function (Agreement $a) use ($data, &$created) {
            $exists = $a->payments()->where('period_year', $data['year'])->where('period_month', $data['month'])->exists();
            if ($exists || $a->gross_total <= 0) {
                return;
            }
            $a->payments()->create([
                'period_year' => $data['year'], 'period_month' => $data['month'],
                'amount' => $a->gross_total, 'status' => 'pending',
            ]);
            $created++;
        });

        $label = SalaryPayment::MONTHS[(int) $data['month']].' '.$data['year'];

        return redirect()->route('agreements.index', ['year' => $data['year'], 'month' => $data['month']])
            ->with('success', $created > 0 ? "{$label} için {$created} tahsilat kaydı açıldı." : "{$label} ayında açılacak yeni tahsilat yok.");
    }

    /** Yeni anlaşma formunun varsayılanları. */
    public static function defaults(): array
    {
        return ['vat_rate' => Setting::amount('vat_rate'), 'm2_rate' => Setting::amount('m2_rate')];
    }
}
