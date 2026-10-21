@php
    $months = \App\Models\SalaryPayment::MONTHS;
    $f = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $byMonth = $agreement->entries->groupBy(fn ($e) => $e->entry_date->format('Y-m'));
    $kindTone = ['payment' => 'badge-ok', 'income' => 'badge-info', 'expense' => 'badge-danger'];
@endphp
<x-app-layout :title="$agreement->name" :subtitle="$agreement->type_label.' anlaşma'.($agreement->status === 'passive' ? ' · pasif' : '')" section="Anlaşmalar">
    <x-slot:actions>
        <a href="{{ route('agreements.index') }}" class="btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Liste</a>
        @can('edit')
            <div x-data="{ edit: false }" class="inline-block">
                <button type="button" @click="edit = true" class="btn-primary btn-sm"><i class="fa-solid fa-pen"></i> Düzenle</button>
                <template x-teleport="body">
                    <template x-if="edit">
                    <div x-show="edit" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                        <form method="POST" action="{{ route('agreements.update', $agreement) }}" class="max-h-[calc(100vh-2rem)] overflow-y-auto w-full max-w-2xl space-y-4 rounded-xl bg-white p-6 shadow-pop text-left">
                            @csrf @method('PATCH')
                            <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Anlaşmayı düzenle</h3><button type="button" @click="edit = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                            @include('agreements._form', ['a' => $agreement])
                            <div class="flex justify-end gap-2"><button type="button" @click="edit = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button></div>
                        </form>
                    </div>
                    </template>
                </template>
            </div>
        @endcan
        @can('delete')
            <x-confirm-form :action="route('agreements.destroy', $agreement)" title="Anlaşmayı sil"
                :message="$agreement->name.' anlaşması, kayıtları ve notları silinecek.'" button="Evet, sil" class="btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Sil</x-confirm-form>
        @endcan
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Alan" :value="$agreement->m2 > 0 ? number_format($agreement->m2, 0, ',', '.').' m²' : '—'" icon="fa-ruler-combined" color="violet" :hint="$agreement->m2_rate > 0 ? $f($agreement->m2_rate).' ₺ / m²' : 'm² fiyatı girilmedi'" />
        <x-stat-card :label="$agreement->type === 'recurring' ? 'Aylık Bedel' : 'Anlaşma Bedeli'" :value="number_format($agreement->gross_total, 2, ',', '.').' ₺'" icon="fa-file-invoice" color="brand" :hint="'KDV hariç '.number_format($agreement->net_total, 2, ',', '.').' ₺ · %'.$f($agreement->vat_rate).' KDV'" />
        <x-stat-card label="Toplam Ödeme" :value="number_format($agreement->paid(), 2, ',', '.').' ₺'" icon="fa-hand-holding-dollar" color="emerald" :hint="$agreement->expected() > 0 ? 'Bekleyen '.number_format($agreement->expected(), 2, ',', '.').' ₺' : 'Ekstra gelir '.number_format($agreement->extraIncome(), 2, ',', '.').' ₺'" />
        <x-stat-card label="Toplam Ekstra Gider" :value="number_format($agreement->extraExpense(), 2, ',', '.').' ₺'" icon="fa-arrow-trend-down" color="amber" :hint="'Net '.number_format($agreement->balance(), 2, ',', '.').' ₺'" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Şirket bilgileri --}}
        <div class="card">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-solid fa-building"></i>Şirket</h2></div>
            <dl class="divide-y divide-slate-50 px-5 py-2 text-sm">
                @foreach ([
                    ['Yetkili', $agreement->contact_name],
                    ['Telefon', $agreement->phone],
                    ['E-posta', $agreement->email],
                    ['Konum', $agreement->address],
                    ['Başlangıç', $agreement->start_date?->format('d.m.Y')],
                    ['Bitiş', $agreement->end_date?->format('d.m.Y')],
                    ['Açıklama', $agreement->note],
                ] as [$k, $v])
                    <div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ $k }}</dt><dd class="text-right font-medium text-slate-800">{{ $v ?: '—' }}</dd></div>
                @endforeach
            </dl>
        </div>

        {{-- Kayıtlar: hangi ay ne ödendi, ekstra gelir / gider --}}
        <div class="card xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900"><i class="fa-solid fa-list-ul"></i>Kayıtlar</h2>
                <span class="text-xs text-slate-500">Ödeme @money($agreement->paid()) · ekstra gelir @money($agreement->extraIncome()) · ekstra gider @money($agreement->extraExpense())@if ($agreement->expected() > 0) · <span class="text-amber-600">bekleyen @money($agreement->expected())</span>@endif</span>
            </div>
            <table class="min-w-full table-hover">
                <thead><tr><th class="th">Tarih</th><th class="th">Tür</th><th class="th">Durum</th><th class="th">Açıklama</th><th class="th text-right">Tutar</th>@can('delete')<th class="th text-right">İşlem</th>@endcan</tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($byMonth as $key => $group)
                    @php $d = \Illuminate\Support\Carbon::parse($key.'-01'); @endphp
                    <tr class="bg-slate-50/70">
                        <td class="px-4 py-2 text-xs font-semibold text-slate-600" colspan="4">{{ $months[(int) $d->month].' '.$d->year }}</td>
                        <td class="px-4 py-2 text-right text-xs font-semibold tabular-nums text-slate-700">@money($group->sum(fn ($e) => $e->signed_amount))</td>
                        @can('delete')<td></td>@endcan
                    </tr>
                    @foreach ($group as $e)
                        <tr>
                            <td class="td whitespace-nowrap text-slate-600">{{ $e->entry_date->format('d.m.Y') }}</td>
                            <td class="td whitespace-nowrap"><span class="{{ $kindTone[$e->kind] }}">{{ $e->kind_label }}</span></td>
                            <td class="td whitespace-nowrap">
                                @can('edit')
                                    <form method="POST" action="{{ route('agreements.entries.status', $e) }}">@csrf
                                        <x-ui-select name="status" :search="false" :submit="true" width="auto" class="status-select status-{{ $e->is_done ? 'paid' : 'pending' }} w-44">@foreach (\App\Models\AgreementEntry::STATUSES as $k => $l)<option value="{{ $k }}" @selected($e->status === $k)>{{ $l }}</option>@endforeach</x-ui-select>
                                    </form>
                                @else
                                    <span class="{{ $e->is_done ? 'badge-ok' : 'badge-warn' }}">{{ $e->status_label }}</span>
                                @endcan
                            </td>
                            <td class="td max-w-[260px] truncate text-slate-700" title="{{ $e->label }}">{{ $e->label ?: '—' }}</td>
                            <td class="td text-right font-semibold tabular-nums {{ ! $e->is_done ? 'text-slate-400' : ($e->kind === 'expense' ? 'text-red-600' : 'text-emerald-700') }}">{{ $e->kind === 'expense' ? '−' : '+' }}@money($e->amount)</td>
                            @can('delete')
                                <td class="td text-right"><x-confirm-form :action="route('agreements.entries.destroy', $e)" title="Kaydı sil" :message="$e->entry_date->format('d.m.Y').' · '.$e->kind_label.' · '.number_format($e->amount, 2, ',', '.').' ₺ silinecek.'"><i class="fa-solid fa-trash"></i></x-confirm-form></td>
                            @endcan
                        </tr>
                    @endforeach
                @empty
                    <tr><td colspan="{{ auth()->user()->can('delete') ? 6 : 5 }}" class="px-5 py-8 text-center text-sm text-slate-500">Henüz kayıt yok. Aşağıdan ödeme, ekstra gelir veya gider ekleyin.</td></tr>
                @endforelse
                </tbody>
            </table>
            @can('edit')
                <form method="POST" action="{{ route('agreements.entries.store', $agreement) }}" class="grid gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:grid-cols-[140px_160px_180px_minmax(0,1fr)_140px]">
                    @csrf
                    <div><label class="form-label">Tarih *</label><x-date-input name="entry_date" :value="now()->toDateString()" :required="true" /></div>
                    <div><label class="form-label">Tür *</label>
                        <x-ui-select name="kind" :search="false">@foreach (\App\Models\AgreementEntry::KINDS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</x-ui-select></div>
                    <div><label class="form-label">Durum *</label>
                        <x-ui-select name="status" :search="false">@foreach (\App\Models\AgreementEntry::STATUSES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</x-ui-select></div>
                    <div><label class="form-label">Açıklama</label><input name="label" placeholder="Örn. Ekim ayı ödemesi, cam silme, malzeme" class="form-input"></div>
                    <div><label class="form-label">Tutar (₺) *</label><input name="amount" required inputmode="decimal" placeholder="{{ number_format($agreement->gross_total, 2, ',', '.') }}" class="form-input text-right"></div>
                    <div class="sm:col-span-5 flex justify-end"><button class="btn-success btn-sm"><i class="fa-solid fa-plus"></i> Kaydet</button></div>
                </form>
            @endcan
        </div>
    </div>

    <div class="card">
        <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-regular fa-note-sticky"></i>Notlar</h2></div>
        <div class="p-5">
            <x-notes-panel :model="$agreement" :action="route('notes.store', ['type' => 'agreements', 'id' => $agreement->id])" :key="'agreements-'.$agreement->id" compact />
        </div>
    </div>
</x-app-layout>
