@php
    $months = \App\Models\SalaryPayment::MONTHS;
    $f = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
@endphp
<x-app-layout :title="$agreement->name" :subtitle="$agreement->type_label.' anlaşma'.($agreement->status === 'passive' ? ' · pasif' : '')" section="Anlaşmalar">
    <x-slot:actions>
        <a href="{{ route('agreements.index') }}" class="btn-secondary btn-sm"><i class="fa-solid fa-arrow-left"></i> Liste</a>
        @can('edit')
            <div x-data="{ edit: false }" class="inline-block">
                <button type="button" @click="edit = true" class="btn-primary btn-sm"><i class="fa-solid fa-pen"></i> Düzenle</button>
                <template x-teleport="body">
                    <template x-if="edit">
                    <div x-show="edit" @click.self="edit = false" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
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
                :message="$agreement->name.' anlaşması, ekstraları, tahsilatları ve notları silinecek.'" button="Evet, sil" class="btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Sil</x-confirm-form>
        @endcan
    </x-slot:actions>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Alan" :value="$agreement->m2 > 0 ? number_format($agreement->m2, 0, ',', '.').' m²' : '—'" icon="fa-ruler-combined" color="violet" :hint="$agreement->m2_rate > 0 ? $f($agreement->m2_rate).' ₺ / m²' : 'm² fiyatı girilmedi'" />
        <x-stat-card label="KDV Hariç" :value="number_format($agreement->net_total, 2, ',', '.').' ₺'" icon="fa-file-invoice" color="sky" :hint="$agreement->extra_charges > 0 ? 'Ana '.number_format($agreement->amount, 2, ',', '.').' + ekstra '.number_format($agreement->extra_charges, 2, ',', '.') : 'Ekstra ücret yok'" />
        <x-stat-card label="KDV Dahil" :value="number_format($agreement->gross_total, 2, ',', '.').' ₺'" icon="fa-percent" color="brand" :hint="'%'.$f($agreement->vat_rate).' KDV = '.number_format($agreement->vat_amount, 2, ',', '.').' ₺'" />
        <x-stat-card label="Ekstra Giderler" :value="number_format($agreement->extra_costs, 2, ',', '.').' ₺'" icon="fa-arrow-trend-down" color="amber" :hint="'Kalan (KDV hariç) '.number_format($agreement->profit, 2, ',', '.').' ₺'" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Kurum bilgileri --}}
        <div class="card">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-solid fa-building"></i>Kurum</h2></div>
            <dl class="divide-y divide-slate-50 px-5 py-2 text-sm">
                @foreach ([
                    ['Yetkili', $agreement->contact_name],
                    ['Telefon', $agreement->phone],
                    ['E-posta', $agreement->email],
                    ['Konum', $agreement->address],
                    ['Başlangıç', $agreement->start_date?->format('d.m.Y')],
                    ['Bitiş', $agreement->end_date?->format('d.m.Y')],
                    ['Ödeme günü', $agreement->payment_day ? 'Ayın '.$agreement->payment_day.'. günü' : null],
                    ['Açıklama', $agreement->note],
                ] as [$k, $v])
                    <div class="flex justify-between gap-3 py-2"><dt class="text-slate-500">{{ $k }}</dt><dd class="text-right font-medium text-slate-800">{{ $v ?: '—' }}</dd></div>
                @endforeach
            </dl>
        </div>

        {{-- Ekstralar --}}
        <div class="card xl:col-span-2">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-solid fa-plus-minus"></i>Ekstra Ücret &amp; Giderler</h2></div>
            <table class="min-w-full table-hover">
                <thead><tr><th class="th">Açıklama</th><th class="th">Tür</th><th class="th">Tekrar</th><th class="th text-right">Tutar</th>@can('delete')<th class="th text-right">İşlem</th>@endcan</tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($agreement->extras as $x)
                    <tr>
                        <td class="td font-medium text-slate-800">{{ $x->label }}</td>
                        <td class="td"><span class="{{ $x->kind === 'charge' ? 'badge-ok' : 'badge-danger' }}">{{ $x->kind_label }}</span></td>
                        <td class="td text-xs text-slate-500">{{ $x->recurring ? 'Her ay' : 'Tek sefer' }}</td>
                        <td class="td text-right font-semibold tabular-nums {{ $x->kind === 'charge' ? 'text-emerald-700' : 'text-red-600' }}">{{ $x->kind === 'charge' ? '+' : '−' }}@money($x->amount)</td>
                        @can('delete')
                            <td class="td text-right"><x-confirm-form :action="route('agreements.extras.destroy', $x)" title="Ekstrayı sil" :message="$x->label.' silinecek.'"><i class="fa-solid fa-trash"></i></x-confirm-form></td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()->can('delete') ? 5 : 4 }}" class="px-5 py-8 text-center text-sm text-slate-500">Ekstra ücret veya gider eklenmemiş.</td></tr>
                @endforelse
                </tbody>
            </table>
            @can('edit')
                <form method="POST" action="{{ route('agreements.extras.store', $agreement) }}" class="grid gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_150px_150px_auto]">
                    @csrf
                    <input name="label" required placeholder="Örn. Cam silme, malzeme bedeli" class="form-input">
                    <x-ui-select name="kind" :search="false">@foreach (\App\Models\AgreementExtra::KINDS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</x-ui-select>
                    <input name="amount" required inputmode="decimal" placeholder="0,00" class="form-input text-right">
                    <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" name="recurring" value="1" checked class="row-check"> Her ay</label>
                    <div class="sm:col-span-4 flex justify-end"><button class="btn-success btn-sm"><i class="fa-solid fa-plus"></i> Ekstra ekle</button></div>
                </form>
            @endcan
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Tahsilatlar --}}
        <div class="card xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900"><i class="fa-solid fa-hand-holding-dollar"></i>Tahsilatlar</h2>
                <span class="text-xs text-slate-500">Tahsil edilen @money($agreement->payments->where('status', 'paid')->sum(fn ($p) => (float) $p->amount)) · bekleyen @money($agreement->payments->where('status', 'pending')->sum(fn ($p) => (float) $p->amount))</span>
            </div>
            <table class="min-w-full table-hover">
                <thead><tr><th class="th">Dönem</th><th class="th text-right">Tutar</th><th class="th">Durum</th><th class="th">Tahsilat</th><th class="th">Açıklama</th>@can('delete')<th class="th text-right">İşlem</th>@endcan</tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($agreement->payments as $p)
                    <tr>
                        <td class="td whitespace-nowrap font-medium text-slate-800">{{ $p->period_label }}</td>
                        <td class="td text-right font-semibold tabular-nums">@money($p->amount)</td>
                        <td class="td whitespace-nowrap">
                            @can('edit')
                                <form method="POST" action="{{ route('agreements.payments.status', $p) }}">@csrf
                                    <x-ui-select name="status" :search="false" :submit="true" width="auto" class="status-select status-{{ $p->status }}">@foreach (\App\Models\AgreementPayment::STATUSES as $k => $l)<option value="{{ $k }}" @selected($p->status === $k)>{{ $l }}</option>@endforeach</x-ui-select>
                                </form>
                            @else
                                <span class="{{ $p->status === 'paid' ? 'badge-ok' : 'badge-warn' }}">{{ $p->status_label }}</span>
                            @endcan
                        </td>
                        <td class="td whitespace-nowrap text-xs text-slate-500">@if ($p->paid_at){{ $p->paid_at->format('d.m.Y') }}@if ($p->method_label) · {{ $p->method_label }}@endif @else — @endif</td>
                        <td class="td max-w-[200px] truncate text-xs text-slate-500" title="{{ $p->note }}">{{ $p->note ?: '—' }}</td>
                        @can('delete')
                            <td class="td text-right"><x-confirm-form :action="route('agreements.payments.destroy', $p)" title="Tahsilatı sil" :message="$p->period_label.' tahsilat kaydı silinecek.'"><i class="fa-solid fa-trash"></i></x-confirm-form></td>
                        @endcan
                    </tr>
                @empty
                    <tr><td colspan="{{ auth()->user()->can('delete') ? 6 : 5 }}" class="px-5 py-8 text-center text-sm text-slate-500">Henüz tahsilat kaydı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
            @can('edit')
                <form method="POST" action="{{ route('agreements.payments.store', $agreement) }}" class="grid gap-3 border-t border-slate-100 bg-slate-50/60 px-5 py-4 sm:grid-cols-3">
                    @csrf
                    <div><label class="form-label">Ay</label>
                        <x-ui-select name="period_month" :search="false" placeholder="—"><option value="">— (tek seferlik)</option>@foreach ($months as $k => $l)<option value="{{ $k }}" @selected($k === (int) now()->month)>{{ $l }}</option>@endforeach</x-ui-select></div>
                    <div><label class="form-label">Yıl</label>
                        <x-ui-select name="period_year" :search="false">@foreach (range(now()->year + 1, 2024) as $y)<option value="{{ $y }}" @selected($y === (int) now()->year)>{{ $y }}</option>@endforeach</x-ui-select></div>
                    <div><label class="form-label">Tutar (KDV dahil)</label><input name="amount" value="{{ number_format($agreement->gross_total, 2, ',', '.') }}" inputmode="decimal" class="form-input text-right"></div>
                    <div><label class="form-label">Durum</label>
                        <x-ui-select name="status" :search="false">@foreach (\App\Models\AgreementPayment::STATUSES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</x-ui-select></div>
                    <div><label class="form-label">Tahsilat tarihi</label><x-date-input name="paid_at" /></div>
                    <div><label class="form-label">Yöntem</label>
                        <x-ui-select name="method" :search="false" placeholder="—"><option value="">—</option>@foreach (\App\Models\AgreementPayment::METHODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</x-ui-select></div>
                    <div class="sm:col-span-3"><label class="form-label">Açıklama</label><input name="note" placeholder="İsteğe bağlı" class="form-input"></div>
                    <div class="sm:col-span-3 flex justify-end"><button class="btn-success btn-sm"><i class="fa-solid fa-plus"></i> Tahsilat ekle / güncelle</button></div>
                </form>
            @endcan
        </div>

        {{-- Notlar --}}
        <div class="card">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-regular fa-note-sticky"></i>Notlar</h2></div>
            <div class="p-5">
                <x-notes-panel :model="$agreement" :action="route('notes.store', ['type' => 'agreements', 'id' => $agreement->id])" :key="'agreements-'.$agreement->id" compact />
            </div>
        </div>
    </div>
</x-app-layout>
