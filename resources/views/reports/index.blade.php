@php $n0 = fn ($v) => number_format((float) $v, 0, ',', '.'); $n2 = fn ($v) => number_format((float) $v, 2, ',', '.'); @endphp
<x-app-layout title="Raporlar" :subtitle="$year.' yılı · maaş, yemek, yol, temizlik ve diğer giderler'">
    <x-slot name="actions">
        <form method="GET" class="flex items-center gap-2">
            <input type="hidden" name="tab" value="{{ request('tab', 'overview') }}">
            <x-ui-select name="year" :search="false" :submit="true" width="auto" class="w-24">
                @foreach ($years as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach
            </x-ui-select>
        </form>
    </x-slot>

    @php $tab = request('tab', 'overview'); @endphp
    <div x-data="{ tab: '{{ $tab }}' }" class="space-y-6">
        {{-- Sekmeler --}}
        <div class="card px-2">
            <div class="flex gap-1 overflow-x-auto">
                @foreach (['overview' => ['fa-chart-pie', 'Genel Özet'], 'monthly' => ['fa-calendar-days', 'Aylık Maliyet'], 'employees' => ['fa-users', 'Personel Bazlı'], 'performance' => ['fa-star-half-stroke', 'Performans']] as $key => [$icon, $label])
                    <button type="button" @click="tab = '{{ $key }}'; history.replaceState(null, '', '?year={{ $year }}&tab={{ $key }}')" :class="tab === '{{ $key }}' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
                            class="-mb-px whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition"><i class="fa-solid {{ $icon }} mr-1.5"></i>{{ $label }}</button>
                @endforeach
            </div>
        </div>

        {{-- Genel özet --}}
        <div x-show="tab === 'overview'" x-cloak class="space-y-6">
    {{-- Yıl özeti --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="card p-5">
            <div class="text-xs font-medium text-slate-500">{{ $year }} Toplam Maliyet</div>
            <div class="mt-2 text-xl font-semibold tracking-tight text-slate-900">{{ $n2($totals['total']) }} ₺</div>
            <div class="mt-1 text-xs text-slate-500">Maaş {{ $n0($totals['salary_net']) }} · giderler {{ $n0($totals['total'] - $totals['salary_net']) }} ₺</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium text-slate-500">Ödenen</div>
            <div class="mt-2 text-xl font-semibold tracking-tight text-emerald-600">{{ $n2($totals['paid']) }} ₺</div>
            <div class="mt-2 flex items-center gap-2"><div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $totals['ratio'] ?? 0 }}%"></div></div><span class="text-xs text-slate-500">%{{ $totals['ratio'] ?? 0 }}</span></div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium text-slate-500">Ödenecek (Kalan)</div>
            <div class="mt-2 text-xl font-semibold tracking-tight {{ $totals['remaining'] > 0 ? 'text-amber-600' : 'text-slate-900' }}">{{ $n2($totals['remaining']) }} ₺</div>
            <div class="mt-1 text-xs text-slate-500">{{ $totals['remaining'] > 0 ? 'Henüz ödenmemiş maaş ve giderler' : 'Tüm ödemeler tamam' }}</div>
        </div>
        <div class="card p-5">
            <div class="text-xs font-medium text-slate-500">Kesintiler</div>
            @php $ded = $totals['salary_deduction'] + $totals['expense_deduction']; @endphp
            <div class="mt-2 text-xl font-semibold tracking-tight {{ $ded > 0 ? 'text-red-600' : 'text-slate-900' }}">{{ $ded > 0 ? '−' : '' }}{{ $n2($ded) }} ₺</div>
            <div class="mt-1 text-xs text-slate-500">Maaş avans/kesinti {{ $n0($totals['salary_deduction']) }} · yemek/yol kesintisi {{ $n0($totals['expense_deduction']) }} ₺</div>
        </div>
    </div>

    {{-- Maliyet dağılımı --}}
    <div class="card p-5">
        <div class="mb-3 flex items-center justify-between"><h2 class="font-semibold text-slate-900"><i class="fa-solid fa-chart-pie mr-2 text-slate-400"></i>Maliyet Dağılımı</h2><span class="text-xs text-slate-500">{{ $year }} · {{ $n2($totals['total']) }} ₺</span></div>
        <div class="flex h-3 w-full overflow-hidden rounded-full bg-slate-100">
            @foreach ($breakdown as $b)@if ($b['value'] > 0)<div style="width: {{ $b['pct'] }}%; background: {{ $b['color'] }}" title="{{ $b['label'] }} · {{ $n2($b['value']) }} ₺ (%{{ $b['pct'] }})"></div>@endif @endforeach
        </div>
        <div class="mt-4 grid gap-3 sm:grid-cols-3 xl:grid-cols-5">
            @foreach ($breakdown as $b)
                <div class="flex items-center gap-3 rounded-lg border border-slate-100 px-3 py-2">
                    <span class="grid h-8 w-8 place-items-center rounded-lg text-white" style="background: {{ $b['color'] }}"><i class="fa-solid {{ $b['icon'] }} text-xs"></i></span>
                    <div class="min-w-0"><div class="text-xs text-slate-500">{{ $b['label'] }} <span class="text-slate-400">%{{ $b['pct'] }}</span></div><div class="truncate text-sm font-semibold text-slate-900">{{ $n2($b['value']) }} ₺</div></div>
                </div>
            @endforeach
        </div>
    </div>

        </div>

        {{-- Aylık --}}
        <div x-show="tab === 'monthly'" x-cloak>
    {{-- Aylık tablo --}}
    <div class="card overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <h2 class="font-semibold text-slate-900"><i class="fa-solid fa-calendar-days mr-2 text-slate-400"></i>Aylık Maliyet ve Ödeme Durumu</h2>
            <div class="flex items-center gap-3 text-[11px] text-slate-500"><span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-emerald-500"></span>Ödendi</span><span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-amber-500"></span>Bekleyen var</span><span><span class="mr-1 inline-block h-2 w-2 rounded-full bg-red-500"></span>Kesinti</span></div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full table-hover">
                <thead><tr>
                    <th class="th">Ay</th><th class="th text-right">Maaş (net)</th><th class="th text-right">Yemek</th><th class="th text-right">Yol</th><th class="th text-right">Ekstra</th><th class="th text-right" title="Temizlik + diğer genel giderler">Genel Gider</th>
                    <th class="th text-right">Kesinti</th><th class="th text-right">Toplam</th><th class="th text-right w-44">Ödenen</th><th class="th">Bekleyen</th><th class="th"></th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                @foreach ($monthly as $m)
                    <tr class="{{ $m['has'] ? '' : 'text-slate-300' }}">
                        <td class="td font-medium {{ $m['has'] ? 'text-slate-900' : '' }}">{{ $m['label'] }}@if ($m['employees'])<div class="text-[11px] font-normal text-slate-400">{{ $m['employees'] }} personel</div>@endif</td>
                        <td class="td text-right tabular-nums">{{ $m['salary_net'] > 0 ? $n2($m['salary_net']) : '—' }}@if ($m['salary_bonus'] > 0)<div class="text-[11px] text-emerald-600">+{{ $n0($m['salary_bonus']) }} prim</div>@endif</td>
                        <td class="td text-right tabular-nums">{{ $m['meal'] > 0 ? $n2($m['meal']) : '—' }}</td>
                        <td class="td text-right tabular-nums">{{ $m['travel'] > 0 ? $n2($m['travel']) : '—' }}</td>
                        <td class="td text-right tabular-nums">{{ $m['extra'] > 0 ? $n2($m['extra']) : '—' }}</td>
                        <td class="td text-right tabular-nums">{{ $m['general'] > 0 ? $n2($m['general']) : '—' }}</td>
                        <td class="td text-right tabular-nums text-red-600">{{ ($m['salary_deduction'] + $m['expense_deduction']) > 0 ? '−'.$n2($m['salary_deduction'] + $m['expense_deduction']) : '—' }}</td>
                        <td class="td text-right font-semibold tabular-nums {{ $m['has'] ? 'text-slate-900' : '' }}">{{ $m['has'] ? $n2($m['total']) : '—' }}</td>
                        <td class="td text-right tabular-nums whitespace-nowrap">
                            @if ($m['has'])
                                <span class="{{ $m['ratio'] >= 100 ? 'text-emerald-600' : 'text-slate-900' }}">{{ $n2($m['paid']) }}</span>
                                <div class="mt-1 flex items-center justify-end gap-2"><div class="h-1.5 w-20 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full {{ $m['ratio'] >= 100 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ min(100, $m['ratio'] ?? 0) }}%"></div></div><span class="w-8 text-right text-[11px] font-medium {{ $m['ratio'] >= 100 ? 'text-emerald-600' : 'text-amber-600' }}">%{{ $m['ratio'] }}</span></div>
                                @if ($m['remaining'] > 0)<div class="text-[11px] text-amber-600">kalan {{ $n0($m['remaining']) }} ₺</div>@endif
                            @else — @endif
                        </td>
                        <td class="td">
                            @php $pend = collect($m['pending'])->filter(); $labels = ['salary' => 'maaş', 'meal' => 'yemek', 'travel' => 'yol', 'extra' => 'ekstra', 'general' => 'genel gider']; @endphp
                            @if ($pend->isEmpty() && $m['has'])<span class="badge-ok">Tamam</span>
                            @elseif ($pend->isNotEmpty())
                                <div class="flex flex-wrap gap-1 whitespace-nowrap">@foreach ($pend as $k => $c)<span class="badge-warn" title="{{ $c }} {{ $labels[$k] }} kaydı ödenmedi">{{ $c }} {{ $labels[$k] }}</span>@endforeach</div>
                            @endif
                        </td>
                        <td class="td text-right whitespace-nowrap">@if ($m['has'])<a href="{{ route('expenses.index', ['year' => $year, 'month' => $m['month']]) }}" class="text-xs font-medium text-brand-600 hover:underline">Muhasebe →</a>@endif</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="border-t border-slate-200 bg-slate-50 text-sm font-semibold"><tr>
                    <td class="px-4 py-3">{{ $year }} Toplamı</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $n2($totals['salary_net']) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $n2($totals['meal']) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $n2($totals['travel']) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $n2($totals['extra']) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $n2($totals['general']) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums {{ ($totals['salary_deduction'] + $totals['expense_deduction']) > 0 ? 'text-red-600' : 'text-slate-400' }}">{{ ($totals['salary_deduction'] + $totals['expense_deduction']) > 0 ? '−'.$n2($totals['salary_deduction'] + $totals['expense_deduction']) : '—' }}</td>
                    <td class="px-4 py-3 text-right tabular-nums">{{ $n2($totals['total']) }}</td>
                    <td class="px-4 py-3 text-right tabular-nums text-emerald-600">{{ $n2($totals['paid']) }}<div class="text-[11px] font-normal text-slate-500">%{{ $totals['ratio'] ?? 0 }}</div></td>
                    <td class="px-4 py-3 text-xs font-normal text-slate-500" colspan="2">Kalan <b class="text-amber-600">{{ $n2($totals['remaining']) }} ₺</b></td>
                </tr></tfoot>
            </table>
        </div>
    </div>

        </div>

        {{-- Personel bazlı --}}
        <div x-show="tab === 'employees'" x-cloak>
        {{-- Personel bazlı --}}
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-solid fa-users mr-2 text-slate-400"></i>Personel Bazlı Yıllık Maliyet</h2></div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-hover">
                    <thead><tr><th class="th">Personel</th><th class="th text-right">Maaş</th><th class="th text-right">Yemek</th><th class="th text-right">Yol</th><th class="th text-right" title="Sağlık raporu, ikramiye vb. kişiye özel ekstra ödemeler">Ekstra</th><th class="th text-right">Diğer</th><th class="th text-right">Kesinti</th><th class="th text-right">Toplam</th><th class="th text-right">Ödenen</th><th class="th text-center">Puan</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($employees as $r)
                        <tr>
                            <td class="td whitespace-nowrap"><a href="{{ route('employees.show', $r['employee']) }}" class="font-medium text-slate-900 hover:text-brand-600">{{ $r['employee']->full_name }}</a>
                                <div class="text-[11px] text-slate-400">{{ $r['months'] }} ay maaş @if ($r['employee']->trashed())· <span class="text-red-500">silindi</span>@elseif ($r['employee']->status === 'passive')· pasif @endif</div></td>
                            <td class="td text-right tabular-nums">{{ $r['salary'] > 0 ? $n2($r['salary']) : '—' }}@if ($r['bonus'] > 0)<div class="text-[11px] text-emerald-600">+{{ $n0($r['bonus']) }} prim</div>@endif</td>
                            <td class="td text-right tabular-nums">{{ $r['meal'] > 0 ? $n2($r['meal']) : '—' }}</td>
                            <td class="td text-right tabular-nums">{{ $r['travel'] > 0 ? $n2($r['travel']) : '—' }}</td>
                            <td class="td text-right tabular-nums {{ $r['extra'] > 0 ? 'font-semibold text-violet-700' : '' }}" title="{{ $r['extra_detail']->map(fn ($v, $k) => (\App\Models\Expense::categories()[$k]['label'] ?? $k).': '.$n2($v).' ₺')->join(' · ') }}">{{ $r['extra'] > 0 ? $n2($r['extra']) : '—' }}</td>
                            <td class="td text-right tabular-nums">{{ $r['other'] > 0 ? $n2($r['other']) : '—' }}</td>
                            <td class="td text-right tabular-nums text-red-600">{{ $r['deduction'] > 0 ? '−'.$n2($r['deduction']) : '—' }}</td>
                            <td class="td text-right font-semibold tabular-nums text-slate-900">{{ $n2($r['total']) }}</td>
                            <td class="td text-right tabular-nums whitespace-nowrap {{ $r['remaining'] > 0 ? 'text-amber-600' : 'text-emerald-600' }}">{{ $n2($r['paid']) }}@if ($r['pending_count'])<div class="text-[11px]">{{ $r['pending_count'] }} bekleyen · kalan {{ $n0($r['remaining']) }}</div>@endif</td>
                            <td class="td text-center"><x-score-badge :score="$r['avg_score']" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="10" class="px-5 py-10 text-center text-sm text-slate-500">{{ $year }} yılında kayıt yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        </div>

        {{-- Performans --}}
        <div x-show="tab === 'performance'" x-cloak>
        <div class="grid gap-6 md:grid-cols-2">
            <div class="card">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-solid fa-trophy mr-2 text-slate-400"></i>En İyi Performans</h2><span class="text-xs text-slate-500">ort. {{ $reviewStats['avg'] ?? '—' }} · {{ $reviewStats['count'] }} değerlendirme</span></div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($reviewStats['top'] as $r)
                        <li class="flex items-center justify-between px-5 py-3 text-sm"><span class="font-medium text-slate-900">{{ $r['employee']->full_name }}</span><x-score-badge :score="$r['avg_score']" /></li>
                    @empty <li class="px-5 py-6 text-center text-sm text-slate-500">Bu yıl değerlendirme yok.</li> @endforelse
                </ul>
            </div>
            <div class="card">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-solid fa-triangle-exclamation mr-2 text-slate-400"></i>Dikkat Gerektiren <span class="text-xs font-normal text-slate-500">(puan &lt; 6)</span></h2></div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($reviewStats['low'] as $r)
                        <li class="flex items-center justify-between px-5 py-3 text-sm"><span class="font-medium text-slate-900">{{ $r['employee']->full_name }}</span><x-score-badge :score="$r['avg_score']" /></li>
                    @empty <li class="px-5 py-6 text-center text-sm text-emerald-700"><i class="fa-solid fa-circle-check mr-1"></i> Düşük puanlı personel yok.</li> @endforelse
                </ul>
            </div>
        </div>
        </div>
    </div>
</x-app-layout>
