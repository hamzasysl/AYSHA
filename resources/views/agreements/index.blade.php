@php
    $months = \App\Models\SalaryPayment::MONTHS;
    $label = $months[$month].' '.$year;
    $chip = fn ($active) => $active ? 'chip chip-active' : 'chip';
    $q = ['year' => $year, 'month' => $month];
@endphp
<x-app-layout title="Anlaşmalar" subtitle="Temizlik hizmeti verdiğimiz şirketler">
    @can('edit')
        <x-slot:actions>
            <div x-data="{ open: false }" class="inline-block">
                <button type="button" @click="open = true" class="btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Yeni Anlaşma</button>
                <template x-teleport="body">
                    <template x-if="open">
                    <div x-show="open" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                        <form method="POST" action="{{ route('agreements.store') }}" class="max-h-[calc(100vh-2rem)] overflow-y-auto w-full max-w-2xl space-y-4 rounded-xl bg-white p-6 shadow-pop text-left">
                            @csrf
                            <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Yeni anlaşma</h3><button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                            @include('agreements._form', ['a' => null])
                            <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button></div>
                        </form>
                    </div>
                    </template>
                </template>
            </div>
        </x-slot:actions>
    @endcan

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Sürekli Anlaşma" :value="$stats['recurring'].' şirket'" icon="fa-repeat" color="brand" :hint="$stats['one_time'].' tek seferlik'" />
        <x-stat-card label="Aylık Bedel (KDV hariç)" :value="number_format($stats['monthly_net'], 2, ',', '.').' ₺'" icon="fa-file-invoice-dollar" color="sky" :hint="'KDV dahil '.number_format($stats['monthly_gross'], 2, ',', '.').' ₺'" />
        <x-stat-card :label="$label.' Ödemesi'" :value="number_format($stats['paid'], 2, ',', '.').' ₺'" icon="fa-hand-holding-dollar" color="emerald" hint="Şirketlerden gelen ödemeler" />
    </div>

    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route('agreements.index', $q) }}" class="{{ $chip(! $type && ! $status) }}"><i class="fa-solid fa-bars"></i> Tümü <b>({{ $counts['all'] }})</b></a>
        <a href="{{ route('agreements.index', $q + ['type' => 'recurring']) }}" class="{{ $chip($type === 'recurring') }}"><i class="fa-solid fa-repeat"></i> Sürekli <b>({{ $counts['recurring'] }})</b></a>
        <a href="{{ route('agreements.index', $q + ['type' => 'one_time']) }}" class="{{ $chip($type === 'one_time') }}"><i class="fa-solid fa-1"></i> Tek seferlik <b>({{ $counts['one_time'] }})</b></a>
        <a href="{{ route('agreements.index', $q + ['status' => 'passive']) }}" class="{{ $chip($status === 'passive') }}"><i class="fa-solid fa-circle-pause"></i> Pasif <b>({{ $counts['passive'] }})</b></a>

        <form method="GET" action="{{ route('agreements.index') }}" class="ml-auto flex items-center gap-2">
            @if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            <x-ui-select name="month" :search="false" :submit="true" width="auto" class="w-32">@foreach ($months as $k => $l)<option value="{{ $k }}" @selected($k === $month)>{{ $l }}</option>@endforeach</x-ui-select>
            <x-ui-select name="year" :search="false" :submit="true" width="auto" class="w-24">@foreach (range(now()->year + 1, 2024) as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach</x-ui-select>
        </form>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full table-hover">
                <thead><tr>
                    <th class="th">Şirket</th><th class="th">Tür</th><th class="th text-right">Alan</th><th class="th text-right">m² Fiyatı</th>
                    <th class="th text-right">KDV Hariç</th><th class="th text-right">KDV Dahil</th>
                    <th class="th text-right" title="{{ $label }} içinde yapılan ödeme">Ödeme</th><th class="th text-right">İşlem</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($agreements as $a)
                    @php $paid = $a->paid($year, $month); @endphp
                    <tr class="hover:bg-slate-50/70" x-data="{ edit: false }">
                        <td class="td whitespace-nowrap">
                            <a href="{{ route('agreements.show', $a) }}" class="font-medium text-slate-900 hover:text-brand-600">{{ $a->name }}</a>
                            <div class="text-[11px] text-slate-500">{{ $a->contact_name ?: '—' }}@if ($a->status === 'passive') · <span class="text-slate-400">pasif</span>@endif</div>
                        </td>
                        <td class="td whitespace-nowrap"><span class="badge{{ $a->type === 'recurring' ? '-info' : '' }}"><i class="fa-solid {{ $a->type === 'recurring' ? 'fa-repeat' : 'fa-1' }}"></i>{{ $a->type_label }}</span></td>
                        <td class="td text-right tabular-nums">{{ $a->m2 > 0 ? number_format($a->m2, 0, ',', '.').' m²' : '—' }}</td>
                        <td class="td text-right tabular-nums">{{ $a->m2_rate > 0 ? number_format($a->m2_rate, 2, ',', '.') : '—' }}</td>
                        <td class="td text-right tabular-nums">@money($a->net_total)</td>
                        <td class="td text-right font-semibold tabular-nums">@money($a->gross_total)<div class="text-[11px] font-normal text-slate-400">%{{ rtrim(rtrim(number_format($a->vat_rate, 2, ',', '.'), '0'), ',') }} KDV</div></td>
                        <td class="td text-right tabular-nums {{ $paid > 0 ? 'font-semibold text-emerald-700' : 'text-slate-400' }}">{{ $paid > 0 ? number_format($paid, 2, ',', '.').' ₺' : '—' }}</td>
                        <td class="td text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1.5">
                                @can('edit')<button type="button" @click="edit = true" class="btn-icon" title="Düzenle"><i class="fa-solid fa-pen"></i></button>@endcan
                                <a href="{{ route('agreements.show', $a) }}" class="btn-icon" title="Kayıtlar ve notlar"><i class="fa-solid fa-arrow-right"></i></a>
                            </div>

                            @can('edit')
                            <template x-teleport="body">
                                <template x-if="edit">
                                <div x-show="edit" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                    <form method="POST" action="{{ route('agreements.update', $a) }}" class="max-h-[calc(100vh-2rem)] overflow-y-auto w-full max-w-2xl space-y-4 rounded-xl bg-white p-6 shadow-pop text-left">
                                        @csrf @method('PATCH')
                                        <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">{{ $a->name }}</h3><button type="button" @click="edit = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                                        @include('agreements._form', ['a' => $a])
                                        <div class="flex justify-end gap-2"><button type="button" @click="edit = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button></div>
                                    </form>
                                </div>
                                </template>
                            </template>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">Bu filtrede anlaşma yok. Sağ üstten <b>Yeni Anlaşma</b> ekleyin.</td></tr>
                @endforelse
                </tbody>
                @if ($agreements->isNotEmpty())
                <tfoot class="bg-slate-50 text-sm font-semibold"><tr>
                    <td class="px-4 py-3" colspan="4">Toplam ({{ $agreements->count() }} şirket)</td>
                    <td class="px-4 py-3 text-right tabular-nums">@money($agreements->sum(fn ($a) => $a->net_total))</td>
                    <td class="px-4 py-3 text-right tabular-nums">@money($agreements->sum(fn ($a) => $a->gross_total))</td>
                    <td class="px-4 py-3 text-right tabular-nums text-emerald-700">@money($agreements->sum(fn ($a) => $a->paid($year, $month)))</td>
                    <td></td>
                </tr></tfoot>
                @endif
            </table>
        </div>
    </div>
</x-app-layout>
