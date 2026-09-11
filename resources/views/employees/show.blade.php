<x-app-layout :title="$employee->full_name" :subtitle="$employee->position.($employee->hire_date ? ' · '.$summary['tenure'].' kıdem' : '')">
    <x-slot name="actions">
        <x-employee-type-badge :retired="$employee->is_retired" />
        @can('edit')
        <a href="{{ route('reviews.create', ['employee_id' => $employee->id]) }}" class="btn-secondary btn-sm"><i class="fa-solid fa-star"></i> Değerlendir</a>
        <a href="{{ route('employees.edit', $employee) }}" class="btn-primary btn-sm"><i class="fa-solid fa-pen"></i> Düzenle</a>
        @endcan
    </x-slot>

    @php $tab = request('tab', 'info'); $self = route('employees.show', ['employee' => $employee, 'tab' => $tab]); @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <x-stat-card label="Aylık Maaş" :value="number_format($employee->salary, 2, ',', '.').' ₺'" icon="fa-wallet" color="sky" />
        <x-stat-card label="Toplam Ödenen Maaş" :value="number_format($summary['total_paid'], 2, ',', '.').' ₺'" icon="fa-circle-check" color="emerald" :hint="$payments->count().' dönem'" />
        <x-stat-card label="Bekleyen Maaş" :value="number_format($summary['total_pending'], 2, ',', '.').' ₺'" icon="fa-clock" :color="$summary['total_pending'] > 0 ? 'amber' : 'slate'" />
        <x-stat-card label="Yemek / Yol / Ekstra" :value="number_format($summary['expenses_total'], 2, ',', '.').' ₺'" icon="fa-calculator" :color="$summary['extra_total'] > 0 ? 'violet' : ($summary['expenses_pending'] > 0 ? 'amber' : 'brand')" :hint="'Ekstra: '.number_format($summary['extra_total'], 2, ',', '.').' ₺ · bekleyen '.number_format($summary['expenses_pending'], 2, ',', '.').' ₺'" />
        <x-stat-card label="Performans" :value="$summary['avg_score'] !== null ? $summary['avg_score'].' / 10' : '—'" icon="fa-star" :color="$summary['avg_score'] >= 8 ? 'emerald' : ($summary['avg_score'] >= 6 ? 'amber' : ($summary['avg_score'] ? 'red' : 'slate'))" :hint="$summary['review_count'].' değerlendirme'" />
    </div>

    <div x-data="{ tab: '{{ $tab }}' }" class="card">
        <div class="flex gap-1 overflow-x-auto border-b border-slate-100 px-3">
            @foreach (['info' => ['fa-id-card', 'Bilgiler'], 'payments' => ['fa-money-bill-transfer', 'Maaş Ödemeleri'], 'expenses' => ['fa-calculator', 'Muhasebe ('.$employee->expenses->count().')'], 'performance' => ['fa-star-half-stroke', 'Performans'], 'leaves' => ['fa-calendar-days', 'İzinler ('.$employee->leaves->count().')'], 'notes' => ['fa-note-sticky', 'Notlar ('.$employee->notes->count().')']] as $key => [$icon, $label])
                <button @click="tab = '{{ $key }}'; history.replaceState(null, '', '?tab={{ $key }}')" :class="tab === '{{ $key }}' ? 'border-brand-600 text-brand-700' : 'border-transparent text-slate-500 hover:text-slate-800'"
                        class="-mb-px whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition"><i class="fa-solid {{ $icon }} mr-1.5"></i>{{ $label }}</button>
            @endforeach
        </div>

        {{-- Bilgiler --}}
        <div x-show="tab === 'info'" x-cloak class="grid gap-8 p-6 md:grid-cols-2">
            @php $row = fn ($l, $val) => '<div class="flex justify-between gap-4 border-b border-slate-50 py-2 text-sm"><span class="text-slate-500">'.$l.'</span><span class="text-right font-medium text-slate-900">'.($val ?: '—').'</span></div>'; @endphp
            <div>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Kimlik ve İletişim</h3>
                {!! $row('Ad Soyad', e($employee->full_name)) !!}
                {!! $row('TC Kimlik No', e($employee->tc_no)) !!}
                {!! $row('Doğum Tarihi', $employee->birth_date?->format('d.m.Y')) !!}
                {!! $row('Telefon', $employee->phone ? '<a class="text-brand-600" href="tel:'.e($employee->phone).'">'.e($employee->phone).'</a>' : null) !!}
                {!! $row('E-posta', e($employee->email)) !!}
                {!! $row('Adres', e($employee->address)) !!}
                {!! $row('Acil Durum', e($employee->emergency_contact)) !!}
            </div>
            <div>
                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">İş ve Banka</h3>
                {!! $row('Görev', e($employee->position)) !!}
                {!! $row('Çalışan tipi', $employee->is_retired
                    ? '<span class="badge-warn"><i class="fa-solid fa-user-clock"></i>Emekli</span>'
                    : '<span class="badge-info"><i class="fa-solid fa-user-check"></i>Normal</span>') !!}
                {!! $row('Durum', '<span class="badge '.($employee->status === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600').'">'.$employee->status_label.'</span>') !!}
                {!! $row('İşe Giriş', $employee->hire_date?->format('d.m.Y')) !!}
                {!! $row('İşten Ayrılış', $employee->termination_date?->format('d.m.Y')) !!}
                {!! $row('Aylık Maaş', number_format($employee->salary, 2, ',', '.').' ₺') !!}
                {!! $row('Yemek ücreti (aylık)', number_format($employee->effective_meal_allowance, 2, ',', '.').' ₺'.($employee->meal_allowance === null ? ' <span class="text-xs font-normal text-slate-400">(standart)</span>' : '')) !!}
                {!! $row('Yol ücreti (aylık)', number_format($employee->effective_travel_allowance, 2, ',', '.').' ₺'.($employee->travel_allowance === null ? ' <span class="text-xs font-normal text-slate-400">(standart)</span>' : '')) !!}
                {!! $row('Banka', e(trim(($employee->bank_name ?? '').($employee->bank_code ? ' (kod '.$employee->bank_code.')' : '')))) !!}
                {!! $row('Şube / Hesap', $employee->branch_code || $employee->account_no ? e(($employee->branch_code ?: '—').' / '.($employee->account_no ?: '—')) : null) !!}
                {!! $row('IBAN', $employee->iban ? '<span class="font-mono" x-data @click="navigator.clipboard.writeText(\''.e($employee->iban).'\')" title="Kopyala" style="cursor:copy">'.e($employee->formatted_iban).' <i class="fa-regular fa-copy text-slate-400"></i></span>' : null) !!}
                {!! $row('Hesap Sahibi', e($employee->account_holder ?: $employee->full_name)) !!}
            </div>
        </div>

        {{-- Maaş Ödemeleri --}}
        <div x-show="tab === 'payments'" x-cloak class="overflow-x-auto">
            <table class="min-w-full">
                <thead><tr><th class="th">Dönem</th><th class="th text-right">Maaş</th><th class="th text-right">Prim</th><th class="th text-right">Avans/Kesinti</th><th class="th text-right">Net</th><th class="th text-right">Ödenen</th><th class="th">Durum</th><th class="th">Ödeme</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="td font-medium text-slate-900"><a href="{{ route('expenses.index', ['category' => 'salary', 'year' => $p->period_year, 'month' => $p->period_month]) }}" class="hover:text-brand-600">{{ $p->period_label }}</a></td>
                        <td class="td text-right">@money($p->base_salary)</td>
                        <td class="td text-right text-emerald-700">{{ $p->bonus > 0 ? '+'.number_format($p->bonus, 2, ',', '.') : '—' }}</td>
                        <td class="td text-right text-red-600">{{ ($p->advance + $p->deduction) > 0 ? '-'.number_format($p->advance + $p->deduction, 2, ',', '.') : '—' }}</td>
                        <td class="td text-right font-semibold">@money($p->net_amount)</td>
                        <td class="td text-right">@money($p->paid_amount)
                            @if ($p->overpaid > 0)<div class="text-[11px] {{ $p->refund_pending > 0 ? 'text-amber-700' : 'text-slate-500' }}">fazla @money($p->overpaid) · {{ $p->refund_pending > 0 ? 'iade bekleniyor' : 'iade alındı' }}</div>@endif</td>
                        <td class="td"><x-status-badge :status="$p->status" /></td>
                        <td class="td text-xs text-slate-500">{{ $p->paid_at ? $p->paid_at->format('d.m.Y') : '' }} {{ $p->method_label }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">Henüz ödeme kaydı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Muhasebe --}}
        <div x-show="tab === 'expenses'" x-cloak class="overflow-x-auto">
            <div class="flex items-center justify-between px-5 py-3 text-sm text-slate-600">
                <span>Yemek ücreti, yol parası ve bu personele yapılan <b>ekstra ödemeler</b> (sağlık raporu, ikramiye vb.).</span>
                <a href="{{ route('expenses.index', ['employee_id' => $employee->id]) }}" class="font-medium text-brand-600 hover:underline">Muhasebe sayfasında aç →</a>
            </div>
            <table class="min-w-full">
                <thead><tr><th class="th">Tarih</th><th class="th">Kategori</th><th class="th">Açıklama</th><th class="th text-right">Tutar</th><th class="th">Durum</th><th class="th">Ödeme</th><th class="th text-center">Not</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($employee->expenses as $x)
                    @php $meta = \App\Models\Expense::categories()[$x->category]; @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="td whitespace-nowrap">{{ $x->expense_date->format('d.m.Y') }}</td>
                        <td class="td"><span class="badge"><i class="fa-solid {{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span></td>
                        <td class="td">{{ $x->description ?: '—' }}</td>
                        <td class="td text-right font-semibold whitespace-nowrap">@money($x->net_amount)
                            @if ($x->deduction > 0)<div class="text-[11px] font-normal text-red-600">kesinti −@money($x->deduction)</div>@endif
                            @if ($x->overpaid > 0)<div class="text-[11px] font-normal {{ $x->refund_pending > 0 ? 'text-amber-700' : 'text-slate-500' }}">fazla @money($x->overpaid) · {{ $x->refund_pending > 0 ? 'iade bekleniyor' : 'iade alındı' }}</div>@endif</td>
                        <td class="td"><x-status-badge :status="$x->status" /></td>
                        <td class="td text-xs text-slate-500 whitespace-nowrap">{{ $x->paid_at?->format('d.m.Y') }} {{ $x->method_label }}</td>
                        <td class="td text-center">@if ($x->notes->count())<span class="badge-info" title="{{ $x->notes->first()->content }}"><i class="fa-regular fa-note-sticky"></i>{{ $x->notes->count() }}</span>@else —@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Bu personel için gider kaydı yok.</td></tr>
                @endforelse
                </tbody>
                @if ($employee->expenses->isNotEmpty())
                <tfoot class="bg-slate-50 text-sm font-semibold"><tr><td class="px-4 py-3" colspan="3">Toplam</td><td class="px-4 py-3 text-right">@money($summary['expenses_total'])</td><td colspan="3" class="px-4 py-3 text-xs font-normal text-slate-500">Bekleyen: @money($summary['expenses_pending'])</td></tr></tfoot>
                @endif
            </table>
        </div>

        {{-- Performans --}}
        <div x-show="tab === 'performance'" x-cloak class="divide-y divide-slate-100">
            @forelse ($employee->performanceReviews as $r)
                <div class="flex flex-col gap-3 p-5 md:flex-row md:items-start">
                    <div class="shrink-0 text-center md:w-28">
                        <div class="text-3xl font-bold {{ $r->score >= 8 ? 'text-emerald-600' : ($r->score >= 6 ? 'text-amber-600' : 'text-red-600') }}">{{ $r->score }}<span class="text-sm text-slate-400">/10</span></div>
                        <div class="text-sm font-semibold text-slate-700">{{ $r->period_label }}</div>
                        <div class="text-[11px] text-slate-500">{{ $r->review_date->format('d.m.Y') }} girildi</div>
                    </div>
                    <div class="flex-1 space-y-2">
                        <div class="flex flex-wrap gap-2 text-xs">
                            @foreach (\App\Models\PerformanceReview::CRITERIA as $k => $l)
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-slate-700">{{ $l }}: <b>{{ $r->$k }}/5</b></span>
                            @endforeach
                        </div>
                        @if ($r->strengths)<p class="text-sm"><span class="font-semibold text-emerald-700">Güçlü:</span> {{ $r->strengths }}</p>@endif
                        @if ($r->improvements)<p class="text-sm"><span class="font-semibold text-amber-700">Gelişim:</span> {{ $r->improvements }}</p>@endif
                        @if ($r->notes)<p class="text-sm text-slate-600">{{ $r->notes }}</p>@endif
                        <div class="text-[11px] text-slate-400">Değerlendiren: {{ $r->reviewer?->name ?? '—' }}</div>
                    </div>
                    <div class="flex shrink-0 gap-1">
                        @can('edit')<a href="{{ route('reviews.edit', $r) }}" class="btn-icon" title="Düzenle"><i class="fa-solid fa-pen"></i></a>@endcan
                        @can('delete')<x-confirm-form :action="route('reviews.destroy', $r)" title="Kaydı sil" message="Bu değerlendirme silinecek." title="Sil"><i class="fa-solid fa-trash"></i></x-confirm-form>@endcan
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">Henüz değerlendirme yok. <a href="{{ route('reviews.create', ['employee_id' => $employee->id]) }}" class="text-brand-600 hover:underline">Geçen ayın değerlendirmesini ekle</a>.</div>
            @endforelse
        </div>

        {{-- İzinler --}}
        <div x-show="tab === 'leaves'" x-cloak>
            @php $fmtd = fn ($d) => rtrim(rtrim(number_format((float) $d, 1, ',', '.'), '0'), ','); @endphp
            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm">
                <div class="text-slate-600">{{ now()->year }} yıllık izin: hak <b>{{ $fmtd($leaveBalance['entitlement']) }}</b> · kullanılan <b>{{ $fmtd($leaveBalance['used']) }}</b> · kalan
                    <span class="{{ $leaveBalance['remaining'] <= 0 ? 'badge-danger' : 'badge-ok' }}">{{ $fmtd($leaveBalance['remaining']) }} gün</span></div>
                @can('edit')<a href="{{ route('leaves.create', ['employee_id' => $employee->id]) }}" class="btn-primary btn-sm"><i class="fa-solid fa-plus"></i> İzin / Devamsızlık Ekle</a>@endcan
            </div>
            <table class="min-w-full">
                <thead><tr><th class="th">Tür</th><th class="th">Tarih</th><th class="th text-center">Gün</th><th class="th text-center">Yıllıktan</th><th class="th">İş başı</th><th class="th">Not</th><th class="th text-right"></th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($employee->leaves as $l)
                    <tr class="hover:bg-slate-50">
                        <td class="td whitespace-nowrap"><span class="badge"><i class="fa-solid {{ \App\Models\Leave::types()[$l->type]['icon'] ?? 'fa-calendar' }}"></i>{{ $l->type_label }}</span> <span class="ml-1 text-[11px] text-slate-400">{{ $l->leave_year }}</span></td>
                        <td class="td whitespace-nowrap">{{ $l->start_date->format('d.m.Y') }}@if (! $l->start_date->equalTo($l->end_date)) – {{ $l->end_date->format('d.m.Y') }}@endif</td>
                        <td class="td text-center font-semibold">{{ $fmtd($l->days) }}</td>
                        <td class="td text-center">@if ($l->deduct_annual)<span class="badge-ok">Düşüldü</span>@else<span class="badge">Düşmedi</span>@endif</td>
                        <td class="td whitespace-nowrap text-slate-500">{{ $l->return_date?->format('d.m.Y') ?? '—' }}</td>
                        <td class="td max-w-[200px] truncate text-xs text-slate-500" title="{{ $l->note }}">{{ $l->note ?: '—' }}</td>
                        <td class="td text-right whitespace-nowrap" x-data="{ notes: false, noteCount: {{ $l->notes->count() }} }" @notes-count.window="if ($event.detail.key === 'leaves-{{ $l->id }}') noteCount = $event.detail.count">
                            <button type="button" @click="notes = true" class="btn-icon relative" title="Notlar"><i class="fa-regular fa-note-sticky"></i><span x-show="noteCount > 0" x-text="noteCount" class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-brand-600 px-1 text-[10px] font-semibold text-white"></span></button>
                            <x-notes-modal show="notes" :model="$l" :action="route('notes.store', ['type' => 'leaves', 'id' => $l->id])" :key="'leaves-'.$l->id" :title="$employee->full_name" :subtitle="$l->type_label.' · '.$fmtd($l->days).' iş günü'" :meta="$l->start_date->format('d.m.Y').($l->start_date->equalTo($l->end_date) ? '' : ' – '.$l->end_date->format('d.m.Y'))" />
                            <a href="{{ route('leaves.print', $l) }}" target="_blank" class="btn-icon" title="İzin formunu yazdır"><i class="fa-solid fa-print"></i></a>
                            @can('edit')<a href="{{ route('leaves.edit', $l) }}" class="btn-icon" title="Düzenle"><i class="fa-solid fa-pen"></i></a>@endcan
                            @can('delete')<x-confirm-form :action="route('leaves.destroy', $l)" title="Kaydı sil" message="Bu izin kaydı silinecek." title="Sil"><i class="fa-solid fa-trash"></i></x-confirm-form>@endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-5 py-10 text-center text-sm text-slate-500">Henüz izin, rapor veya devamsızlık kaydı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Notlar --}}
        <div x-show="tab === 'notes'" x-cloak class="p-5">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-medium text-slate-900"><i class="fa-regular fa-note-sticky text-slate-400"></i><span>{{ $employee->full_name }} <span class="text-slate-400">· notlar</span></span></h3>
            <x-notes-panel :model="$employee" :action="route('notes.store', ['type' => 'employees', 'id' => $employee->id])" :key="'employees-'.$employee->id" class="max-w-3xl" />
        </div>
    </div>

    @can('delete')
    <div class="flex justify-end">
        <x-confirm-form :action="route('employees.destroy', $employee)" :message="$employee->full_name.' kaydı ve tüm geçmişi silinecek.'" class="btn btn-sm btn-danger"><i class="fa-solid fa-trash"></i> Personeli Sil</x-confirm-form>
    </div>
    @endcan
</x-app-layout>
