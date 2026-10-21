<?php

namespace App\Http\Controllers;

use App\Http\Requests\AgreementRequest;
use App\Models\Agreement;
use App\Models\AgreementEntry;
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

        $all = Agreement::with(['entries', 'notes'])->orderBy('name')->get();
        $agreements = $all
            ->when($type, fn ($c) => $c->where('type', $type))
            ->when($status, fn ($c) => $c->where('status', $status))
            ->values();

        $activeRecurring = $all->where('status', 'active')->where('type', 'recurring');

        $stats = [
            'recurring' => $activeRecurring->count(),
            'one_time' => $all->where('status', 'active')->where('type', 'one_time')->count(),
            'monthly_net' => $activeRecurring->sum(fn ($a) => $a->net_total),
            'monthly_gross' => $activeRecurring->sum(fn ($a) => $a->gross_total),
            'paid' => $all->sum(fn ($a) => $a->paid($year, $month)),
            'income' => $all->sum(fn ($a) => $a->extraIncome($year, $month)),
            'expense' => $all->sum(fn ($a) => $a->extraExpense($year, $month)),
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
        $agreement->load(['entries', 'notes']);

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

    // ---- Hareketler (ödeme / ekstra gelir / ekstra gider) ----

    public function storeEntry(Request $request, Agreement $agreement): RedirectResponse
    {
        $entry = $agreement->entries()->create($this->entryData($request));

        return back()->with('success', $entry->kind_label.' kaydedildi.');
    }

    public function updateEntry(Request $request, AgreementEntry $entry): RedirectResponse
    {
        $entry->update($this->entryData($request));

        return back()->with('success', 'Kayıt güncellendi.');
    }

    public function destroyEntry(AgreementEntry $entry): RedirectResponse
    {
        $entry->delete();

        return back()->with('success', 'Kayıt silindi.');
    }

    private function entryData(Request $request): array
    {
        $request->merge(['amount' => str_replace(['.', ','], ['', '.'], preg_replace('/[^\d,.]/', '', (string) $request->input('amount')))]);

        return $request->validate([
            'entry_date' => ['required', 'date'],
            'kind' => ['required', Rule::in(array_keys(AgreementEntry::KINDS))],
            'label' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0', 'max:99999999'],
        ], [], ['entry_date' => 'tarih', 'kind' => 'tür', 'label' => 'açıklama', 'amount' => 'tutar']);
    }

    /** Yeni anlaşma formunun varsayılanları. */
    public static function defaults(): array
    {
        return ['vat_rate' => Setting::amount('vat_rate'), 'm2_rate' => Setting::amount('m2_rate')];
    }
}
