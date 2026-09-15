<x-app-layout title="Genel Bakış" :subtitle="\App\Models\SalaryPayment::MONTHS[$month].' '.$year.' dönemi özeti'">
    <x-slot name="actions">
        @can('edit')<a href="{{ route('employees.create') }}" class="btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Yeni Personel</a>@endcan
    </x-slot>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="Aktif Personel" :value="$stats['active']" icon="fa-users" color="brand" :hint="$stats['passive'].' pasif'" />
        <x-stat-card label="Aylık Maaş Yükü" :value="number_format($stats['monthly_payroll'], 2, ',', '.').' ₺'" icon="fa-wallet" color="sky" hint="Aktif personel maaş toplamı" />
        <x-stat-card label="Bu Ay Ödenen" :value="number_format($stats['period_paid'], 2, ',', '.').' ₺'" icon="fa-circle-check" color="emerald"
                     :hint="$stats['period_generated'] ? 'Net: '.number_format($stats['period_net'], 2, ',', '.').' ₺' : 'Bordro henüz oluşturulmadı'" />
        <x-stat-card label="Bekleyen Ödeme" :value="$stats['period_pending_count'].' kişi'" icon="fa-clock" :color="$stats['period_pending_count'] > 0 ? 'amber' : 'slate'"
                     :hint="'Kalan: '.number_format($stats['period_net'] - $stats['period_paid'], 2, ',', '.').' ₺'.($stats['refund_pending'] > 0 ? ' · iade bekleyen '.number_format($stats['refund_pending'], 2, ',', '.').' ₺' : '')" />
        <x-stat-card label="Bu Ay Giderler" :value="number_format($stats['period_expenses'], 2, ',', '.').' ₺'" icon="fa-calculator" :color="$stats['period_expenses_pending'] > 0 ? 'amber' : 'brand'"
                     :hint="'Yemek, yol, temizlik · bekleyen '.number_format($stats['period_expenses_pending'], 0, ',', '.').' ₺'" />
    </div>

    <div class="grid gap-6 xl:grid-cols-3">
        {{-- Bekleyen ödemeler --}}
        <div class="card xl:col-span-2">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900"><i class="fa-solid fa-money-bill-transfer mr-2 text-slate-400"></i>Bu Ay Bekleyen Ödemeler</h2>
                <a href="{{ route('expenses.index', ['category' => 'salary', 'year' => $year, 'month' => $month]) }}" class="text-sm font-medium text-brand-600 hover:underline">Tümünü gör →</a>
            </div>
            @if (! $stats['period_generated'])
                <div class="p-8 text-center text-sm text-slate-500">
                    <i class="fa-regular fa-folder-open mb-2 text-3xl text-slate-300"></i>
                    <p>Bu ayın bordrosu henüz oluşturulmadı.</p>
                    @can('edit')<form method="POST" action="{{ route('payments.generate') }}" class="mt-3">
                        @csrf
                        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <button class="btn-primary btn-sm"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ \App\Models\SalaryPayment::MONTHS[$month] }} bordrosunu oluştur</button>
                    </form>@endcan
                </div>
            @elseif ($pendingPayments->isEmpty())
                <div class="p-8 text-center text-sm text-emerald-700"><i class="fa-solid fa-circle-check mr-1"></i> Bu ay tüm maaşlar ödendi.</div>
            @else
                <table class="min-w-full table-hover">
                    <thead><tr><th class="th">Personel</th><th class="th text-right">Net</th><th class="th text-right">Kalan</th><th class="th">Durum</th><th class="th text-right"></th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach ($pendingPayments as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="td font-medium text-slate-900"><a href="{{ route('employees.show', $p->employee) }}" class="hover:text-brand-600">{{ $p->employee->full_name }}</a></td>
                            <td class="td text-right">@money($p->net_amount)</td>
                            <td class="td text-right font-semibold text-red-600">@money($p->remaining)</td>
                            <td class="td"><x-status-badge :status="$p->status" /></td>
                            <td class="td text-right">
                                @can('edit')<x-confirm-form :action="route('payments.mark-paid', $p)" method="POST" variant="success" title="Maaş ödendi olarak işaretle"
                                    :message="$p->employee->full_name.' · kalan '.number_format($p->remaining, 2, ',', '.').' ₺ bugün ödendi olarak işaretlenecek.'"
                                    button="Evet, ödendi" title="Ödendi işaretle"><i class="fa-solid fa-check"></i></x-confirm-form>@endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Son 6 ay --}}
        <div class="card">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-solid fa-chart-column mr-2 text-slate-400"></i>Son 6 Ay Bordro</h2></div>
            @php $max = max(1, $trend->max('net')); @endphp
            <div class="space-y-3 p-5">
                @foreach ($trend as $t)
                    <div>
                        <div class="mb-1 flex justify-between text-xs"><span class="font-medium text-slate-700">{{ $t['label'] }}</span><span class="text-slate-500">{{ number_format($t['paid'], 0, ',', '.') }} / {{ number_format($t['net'], 0, ',', '.') }} ₺</span></div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-100">
                            <div class="relative h-full rounded-full bg-brand-100" style="width: {{ round($t['net'] / $max * 100) }}%">
                                <div class="h-full rounded-full bg-brand-500" style="width: {{ $t['net'] > 0 ? round($t['paid'] / $t['net'] * 100) : 0 }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
                <div class="flex items-center gap-4 pt-1 text-[11px] text-slate-500"><span><span class="inline-block h-2 w-2 rounded-full bg-brand-500"></span> Ödenen</span><span><span class="inline-block h-2 w-2 rounded-full bg-brand-100"></span> Net toplam</span></div>
            </div>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="card">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h2 class="font-semibold text-slate-900"><i class="fa-solid fa-star-half-stroke mr-2 text-slate-400"></i>Son Değerlendirmeler</h2>
                <span class="text-xs text-slate-500">Son 3 ay ort. <b>{{ $stats['avg_score'] ?: '—' }}</b>/10</span>
            </div>
            <ul class="divide-y divide-slate-100">
                @forelse ($recentReviews as $r)
                    <li class="flex items-center gap-3 px-5 py-3">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('employees.show', ['employee' => $r->employee, 'tab' => 'performance']) }}" class="block truncate text-sm font-medium text-slate-900 hover:text-brand-600">{{ $r->employee->full_name }}</a>
                            <div class="text-xs text-slate-500">{{ $r->period_label }} · {{ $r->review_date->format('d.m.Y') }}</div>
                        </div>
                        <x-score-badge :score="$r->score" />
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-slate-500">Henüz değerlendirme yok.</li>
                @endforelse
            </ul>
        </div>
        <div class="card">
            <div class="border-b border-slate-100 px-5 py-4"><h2 class="font-semibold text-slate-900"><i class="fa-regular fa-note-sticky mr-2 text-slate-400"></i>Son Notlar</h2></div>
            <ul class="divide-y divide-slate-100">
                @forelse ($recentNotes as $n)
                    @php
                        $target = $n->notable;
                        [$label, $url] = match (true) {
                            $target instanceof \App\Models\Employee => [$target->full_name, route('employees.show', ['employee' => $target, 'tab' => 'notes'])],
                            $target instanceof \App\Models\Expense => [$target->category_label.($target->employee ? ' · '.$target->employee->full_name : ''), route('expenses.index', ['year' => $target->expense_date->year, 'month' => $target->expense_date->month])],
                            $target instanceof \App\Models\Leave => [$target->type_label.($target->employee ? ' · '.$target->employee->full_name : ''), route('leaves.index', ['year' => $target->leave_year])],
                            default => ['—', '#'],
                        };
                    @endphp
                    <li class="px-5 py-3">
                        <div class="flex items-center justify-between text-xs text-slate-500">
                            <a href="{{ $url }}" class="font-medium text-slate-800 hover:text-brand-600">{{ $label }}</a>
                            <span title="{{ $n->created_at->format('d.m.Y H:i') }}">{{ $n->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-700">{{ $n->content }}</p>
                    </li>
                @empty
                    <li class="px-5 py-6 text-center text-sm text-slate-500">Henüz not yok.</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-app-layout>
