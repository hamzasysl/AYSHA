@php
    $months = \App\Models\SalaryPayment::MONTHS;
    $label = $months[$month].' '.$year;
    $chip = fn ($active) => $active ? 'chip chip-active' : 'chip';
    $q = ['year' => $year, 'month' => $month];
@endphp
<x-app-layout title="Anlaşmalar" subtitle="Temizlik hizmeti verdiğimiz kurumlar ve aylık tahsilatlar">
    @can('edit')
        <x-slot:actions>
            <form method="POST" action="{{ route('agreements.generate-payments') }}">@csrf
                <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                <button class="btn-secondary btn-sm" title="Sürekli anlaşmalara {{ $label }} için bekleyen tahsilat kaydı açar"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ $label }} Tahsilatları</button>
            </form>
            <div x-data="{ open: false }" class="inline-block">
                <button type="button" @click="open = true" class="btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Yeni Anlaşma</button>
                <template x-teleport="body">
                    <template x-if="open">
                    <div x-show="open" @click.self="open = false" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
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

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Sürekli Anlaşma" :value="$stats['recurring'].' kurum'" icon="fa-repeat" color="brand" :hint="$stats['one_time'].' tek seferlik'" />
        <x-stat-card label="Aylık Gelir (KDV hariç)" :value="number_format($stats['monthly_net'], 2, ',', '.').' ₺'" icon="fa-file-invoice-dollar" color="sky" :hint="'KDV dahil '.number_format($stats['monthly_gross'], 2, ',', '.').' ₺'" />
        <x-stat-card :label="$label.' Tahsil Edilen'" :value="number_format($stats['collected'], 2, ',', '.').' ₺'" icon="fa-circle-check" color="emerald" :hint="'Bekleyen '.number_format($stats['pending'], 2, ',', '.').' ₺'" />
        <x-stat-card label="Aylık Ekstra Gider" :value="number_format($stats['monthly_costs'], 2, ',', '.').' ₺'" icon="fa-arrow-trend-down" color="amber" hint="Sürekli anlaşmaların maliyeti" />
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
                    <th class="th">Kurum</th><th class="th">Tür</th><th class="th text-right">Alan</th><th class="th text-right">m² Fiyatı</th>
                    <th class="th text-right">KDV Hariç</th><th class="th text-right">KDV Dahil</th><th class="th">Ekstralar</th>
                    <th class="th">{{ $label }}</th><th class="th text-right">İşlem</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($agreements as $a)
                    @php $pay = $a->paymentFor($year, $month); @endphp
                    <tr class="hover:bg-slate-50/70">
                        <td class="td whitespace-nowrap">
                            <a href="{{ route('agreements.show', $a) }}" class="font-medium text-slate-900 hover:text-brand-600">{{ $a->name }}</a>
                            <div class="text-[11px] text-slate-500">{{ $a->contact_name ?: ($a->note ?: '—') }}@if ($a->status === 'passive') · <span class="text-slate-400">pasif</span>@endif</div>
                        </td>
                        <td class="td whitespace-nowrap"><span class="badge{{ $a->type === 'recurring' ? '-info' : '' }}"><i class="fa-solid {{ $a->type === 'recurring' ? 'fa-repeat' : 'fa-1' }}"></i>{{ $a->type_label }}</span></td>
                        <td class="td text-right tabular-nums">{{ $a->m2 > 0 ? number_format($a->m2, 0, ',', '.').' m²' : '—' }}</td>
                        <td class="td text-right tabular-nums">{{ $a->m2_rate > 0 ? number_format($a->m2_rate, 2, ',', '.') : '—' }}</td>
                        <td class="td text-right tabular-nums">@money($a->net_total)
                            @if ($a->extra_charges > 0)<div class="text-[11px] font-normal text-slate-400">ana {{ number_format($a->amount, 2, ',', '.') }} + ekstra {{ number_format($a->extra_charges, 2, ',', '.') }}</div>@endif
                        </td>
                        <td class="td text-right font-semibold tabular-nums">@money($a->gross_total)<div class="text-[11px] font-normal text-slate-400">%{{ rtrim(rtrim(number_format($a->vat_rate, 2, ',', '.'), '0'), ',') }} KDV</div></td>
                        <td class="td whitespace-nowrap">
                            @if ($a->extra_charges > 0)<span class="badge-ok" title="Faturaya eklenen ekstra ücretler">+@money($a->extra_charges)</span>@endif
                            @if ($a->extra_costs > 0)<span class="badge-danger" title="Bizim ekstra giderimiz">−@money($a->extra_costs)</span>@endif
                            @if ($a->extra_charges <= 0 && $a->extra_costs <= 0)<span class="text-slate-400">—</span>@endif
                        </td>
                        <td class="td whitespace-nowrap">
                            @if ($pay)
                                <span class="{{ $pay->status === 'paid' ? 'badge-ok' : 'badge-warn' }}" title="{{ number_format($pay->amount, 2, ',', '.') }} ₺">{{ $pay->status_label }}</span>
                            @elseif ($a->type === 'recurring' && $a->status === 'active')
                                <span class="text-xs text-slate-400">kayıt yok</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="td text-right whitespace-nowrap">
                            <a href="{{ route('agreements.show', $a) }}" class="btn-icon" title="Detay"><i class="fa-solid fa-arrow-right"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-5 py-12 text-center text-sm text-slate-500">Bu filtrede anlaşma yok. Sağ üstten <b>Yeni Anlaşma</b> ekleyin.</td></tr>
                @endforelse
                </tbody>
                @if ($agreements->isNotEmpty())
                <tfoot class="bg-slate-50 text-sm font-semibold"><tr>
                    <td class="px-4 py-3" colspan="4">Toplam ({{ $agreements->count() }} kurum)</td>
                    <td class="px-4 py-3 text-right tabular-nums">@money($agreements->sum(fn ($a) => $a->net_total))</td>
                    <td class="px-4 py-3 text-right tabular-nums">@money($agreements->sum(fn ($a) => $a->gross_total))</td>
                    <td class="px-4 py-3 text-xs font-normal text-slate-500" colspan="3">Ekstra ücret @money($agreements->sum(fn ($a) => $a->extra_charges)) · ekstra gider @money($agreements->sum(fn ($a) => $a->extra_costs))</td>
                </tr></tfoot>
                @endif
            </table>
        </div>
    </div>
</x-app-layout>
