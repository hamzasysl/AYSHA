@php
    $months = \App\Models\SalaryPayment::MONTHS;
    $label = $months[$month].' '.$year;
    $q = ['year' => $year, 'month' => $month];
    [$dy, $dm] = \App\Models\PerformanceReview::defaultPeriod();
@endphp
<x-app-layout title="Performans" :subtitle="$label.' dönemi · her ay başında bir önceki ayın değerlendirmesi girilir'">
    <x-slot name="actions">
        <form method="GET" class="flex items-center gap-2">
            @if ($employeeId)<input type="hidden" name="employee_id" value="{{ $employeeId }}">@endif
            <x-ui-select name="month" :search="false" :submit="true" width="auto" class="w-36">
                @foreach ($months as $m => $ml)<option value="{{ $m }}" @selected($m === $month)>{{ $ml }}</option>@endforeach
            </x-ui-select>
            <x-ui-select name="year" :search="false" :submit="true" width="auto" class="w-24">
                @foreach (range(now()->year + 1, now()->year - 3) as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach
            </x-ui-select>
        </form>
        @can('edit')<a href="{{ route('reviews.create', $q) }}" class="btn-primary btn-sm"><i class="fa-solid fa-star"></i> Değerlendir</a>@endcan
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="Değerlendirilen" :value="$stats['rated'].' / '.$stats['total']" icon="fa-clipboard-check" :color="$stats['rated'] === $stats['total'] && $stats['total'] > 0 ? 'emerald' : 'brand'" :hint="$label.' · '.($stats['total'] - $stats['rated']).' personel bekliyor'" />
        <x-stat-card label="Dönem Ortalaması" :value="$stats['avg'] !== null ? $stats['avg'].' / 10' : '—'" icon="fa-star" :color="$stats['avg'] >= 8 ? 'emerald' : ($stats['avg'] >= 6 ? 'amber' : ($stats['avg'] ? 'red' : 'slate'))" />
        <x-stat-card label="Dikkat Gerektiren" :value="$stats['low']" icon="fa-triangle-exclamation" :color="$stats['low'] > 0 ? 'red' : 'slate'" hint="Bu ay 6'nın altında puan alan" />
        <div class="card p-5 text-sm text-slate-600">
            <div class="mb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-500">Nasıl çalışır</div>
            Her personele <b>ayda bir</b> değerlendirme girilir. Varsayılan dönem geçen ay (<a href="{{ route('reviews.index', ['year' => $dy, 'month' => $dm]) }}" class="text-brand-600 hover:underline">{{ $months[$dm] }} {{ $dy }}</a>). Üstten ay seçerek geriye dönük de girebilirsiniz.
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Dönem tablosu --}}
        <div class="card overflow-hidden xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900"><i class="fa-solid fa-calendar-check mr-2 text-slate-400"></i>{{ $label }} Değerlendirmeleri</h2>
                <span class="text-xs text-slate-500">{{ $stats['rated'] }} / {{ $stats['total'] }} tamamlandı</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-hover">
                    <thead><tr><th class="th">Personel</th><th class="th">Durum</th><th class="th text-center" title="Devam · İş kalitesi · Tutum">Kriterler</th><th class="th">Not</th><th class="th text-right">İşlem</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($rows as $row)
                        @php $e = $row['employee']; $r = $row['review']; @endphp
                        <tr>
                            <td class="td whitespace-nowrap">
                                <span class="font-semibold text-slate-900">{{ $e->full_name }}</span>
                                <div class="text-[11px] text-slate-500">{{ $e->position }}</div>
                            </td>
                            @if ($r)
                                <td class="td whitespace-nowrap"><x-score-badge :score="$r->score" /></td>
                                <td class="td text-center whitespace-nowrap"><span class="text-xs font-semibold text-slate-600" title="Devam {{ $r->attendance }} · İş kalitesi {{ $r->quality }} · Tutum {{ $r->attitude }}">{{ $r->attendance }} · {{ $r->quality }} · {{ $r->attitude }}</span></td>
                                <td class="td max-w-[260px]">
                                    <div class="truncate text-xs text-slate-600" title="{{ $r->strengths }} {{ $r->improvements }}">{{ \Illuminate\Support\Str::limit($r->strengths ?: $r->improvements ?: $r->notes ?: '—', 60) }}</div>
                                    <div class="text-[11px] text-slate-400">{{ $r->review_date->format('d.m.Y') }} · {{ $r->reviewer?->name ?? '—' }}</div>
                                </td>
                                <td class="td text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('reviews.index', $q + ['employee_id' => $e->id]) }}" class="btn-icon" title="Tüm aylardaki puanlarını gör"><i class="fa-solid fa-chart-line"></i></a>
                                        @can('edit')<a href="{{ route('reviews.edit', $r) }}" class="btn-icon" title="Düzenle"><i class="fa-solid fa-pen"></i></a>@endcan
                                        @can('delete')<x-confirm-form :action="route('reviews.destroy', $r)" title="Kaydı sil" message="Bu değerlendirme silinecek." title="Sil"><i class="fa-solid fa-trash"></i></x-confirm-form>@endcan
                                    </div>
                                </td>
                            @else
                                <td class="td whitespace-nowrap"><span class="text-xs text-slate-400">Henüz değerlendirilmedi</span></td>
                                <td class="td text-center text-slate-300">—</td>
                                <td class="td text-slate-300">—</td>
                                <td class="td text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('reviews.index', $q + ['employee_id' => $e->id]) }}" class="btn-icon" title="Tüm aylardaki puanlarını gör"><i class="fa-solid fa-chart-line"></i></a>
                                        @can('edit')<a href="{{ route('reviews.create', $q + ['employee_id' => $e->id]) }}" class="btn btn-sm btn-primary"><i class="fa-solid fa-star"></i> Değerlendir</a>@endcan
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-5 py-12 text-center text-sm text-slate-500">Aktif personel yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-6">
            {{-- Seçili personelin ay ay geçmişi --}}
            @if ($selectedEmployee)
                <div class="card">
                    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                        <h2 class="font-semibold text-slate-900"><i class="fa-solid fa-chart-line mr-2 text-slate-400"></i>{{ $selectedEmployee->full_name }} <span class="text-xs font-normal text-slate-500">· tüm aylar</span></h2>
                        <a href="{{ route('reviews.index', $q) }}" class="text-slate-400 hover:text-slate-700" title="Kapat"><i class="fa-solid fa-xmark"></i></a>
                    </div>
                    <ul class="divide-y divide-slate-100">
                        @forelse ($history as $h)
                            <li class="flex items-center gap-3 px-5 py-2.5 text-sm">
                                <a href="{{ route('reviews.index', ['year' => $h->period_year, 'month' => $h->period_month, 'employee_id' => $selectedEmployee->id]) }}" class="flex-1 font-medium text-slate-800 hover:text-brand-600">{{ $h->period_label }}</a>
                                <span class="text-[11px] text-slate-400">{{ $h->attendance }}·{{ $h->quality }}·{{ $h->attitude }}</span>
                                <x-score-badge :score="$h->score" />
                                @can('edit')<a href="{{ route('reviews.edit', $h) }}" class="text-slate-400 hover:text-brand-600"><i class="fa-solid fa-pen text-xs"></i></a>@endcan
                            </li>
                        @empty
                            <li class="px-5 py-6 text-center text-sm text-slate-500">Henüz değerlendirme yok.</li>
                        @endforelse
                    </ul>
                </div>
            @else
                <div class="card p-5">
                    <form method="GET" class="space-y-2">
                        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <label class="form-label">Bir personelin tüm değerlendirmeleri</label>
                        <x-ui-select name="employee_id" :submit="true" placeholder="Personel seçin, ay ay puanlarını görün">
                            <option value="">Personel seçin, ay ay puanlarını görün</option>
                            @foreach ($allEmployees as $e)<option value="{{ $e->id }}">{{ $e->full_name }}{{ $e->status === 'passive' ? ' (pasif)' : '' }}</option>@endforeach
                        </x-ui-select>
                    </form>
                </div>
            @endif

            {{-- Genel sıralama --}}
            <div class="card">
                <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-solid fa-ranking-star mr-2 text-slate-400"></i>Genel Sıralama <span class="text-xs font-normal text-slate-500">(tüm aylar)</span></h2></div>
                <ul class="divide-y divide-slate-100">
                    @forelse ($leaderboard as $i => $e)
                        <li class="flex items-center gap-3 px-5 py-2.5">
                            <span class="w-5 text-center text-xs font-bold {{ $i < 3 ? 'text-amber-500' : 'text-slate-400' }}">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('reviews.index', $q + ['employee_id' => $e->id]) }}" class="block truncate text-sm font-medium text-slate-900 hover:text-brand-600">{{ $e->full_name }}</a>
                                <div class="text-[11px] text-slate-500">{{ $e->performance_reviews_count }} ay</div>
                            </div>
                            <x-score-badge :score="round($e->avg_score, 1)" />
                        </li>
                    @empty
                        <li class="px-5 py-6 text-center text-sm text-slate-500">Henüz değerlendirme yok.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
