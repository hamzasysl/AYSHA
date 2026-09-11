<x-app-layout title="Personel" subtitle="Ekip listesi, maaş ve banka bilgileri">
    <x-slot name="actions">
        @can('edit')<a href="{{ route('employees.create') }}" class="btn-primary btn-sm"><i class="fa-solid fa-user-plus"></i> Yeni Personel</a>@endcan
    </x-slot>

    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap gap-2">
            @php $chip = fn ($active, $color = null) => $active ? "chip chip-active" : "chip"; @endphp
            <a href="{{ route('employees.index', ['q' => $search]) }}" class="{{ $chip(! $status, 'brand') }}"><i class="fa-solid fa-bars"></i> Tümü <b>({{ $stats['total'] }})</b></a>
            <a href="{{ route('employees.index', ['status' => 'active', 'q' => $search]) }}" class="{{ $chip($status === 'active', 'emerald') }}"><i class="fa-solid fa-circle-check"></i> Aktif <b>({{ $stats['active'] }})</b></a>
            <a href="{{ route('employees.index', ['status' => 'passive', 'q' => $search]) }}" class="{{ $chip($status === 'passive', 'slate') }}"><i class="fa-solid fa-circle-minus"></i> Pasif <b>({{ $stats['passive'] }})</b></a>
            <span class="mx-1 hidden w-px bg-slate-200 sm:block"></span>
            <a href="{{ route('employees.index', ['status' => $status, 'q' => $search, 'type' => $type === 'normal' ? null : 'normal']) }}" class="{{ $chip($type === 'normal', 'brand') }}"><i class="fa-solid fa-user-check"></i> Normal <b>({{ $stats['normal'] }})</b></a>
            <a href="{{ route('employees.index', ['status' => $status, 'q' => $search, 'type' => $type === 'retired' ? null : 'retired']) }}" class="{{ $chip($type === 'retired', 'amber') }}"><i class="fa-solid fa-user-clock"></i> Emekli <b>({{ $stats['retired'] }})</b></a>
        </div>
        <form method="GET" class="flex gap-2">
            @if ($status)<input type="hidden" name="status" value="{{ $status }}">@endif
            @if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif
            <input type="search" name="q" value="{{ $search }}" placeholder="Ad, telefon, TC, görev..." class="form-input w-64">
            <button class="btn-secondary"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
    </div>

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full table-hover">
                <thead><tr>
                    <th class="th">Personel</th><th class="th">Tip</th><th class="th">Görev</th><th class="th">Telefon</th>
                    <th class="th text-right">Maaş</th><th class="th text-center">Yıllık İzin</th><th class="th">İşe Giriş</th>
                    <th class="th text-center">Puan</th><th class="th">Durum</th><th class="th text-right">İşlem</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($employees as $e)
                    <tr class="hover:bg-slate-50/70">
                        <td class="td">
                            <div class="flex items-center gap-3">
                                <span><span class="block font-semibold text-slate-900">{{ $e->full_name }}</span>
                                @if ($e->notes_count)<span class="text-[11px] text-slate-500"><i class="fa-regular fa-note-sticky"></i> {{ $e->notes_count }} not</span>@endif</span>
                            </div>
                        </td>
                        <td class="td"><x-employee-type-badge :retired="$e->is_retired" /></td>
                        <td class="td whitespace-nowrap">{{ $e->position }}</td>
                        <td class="td whitespace-nowrap">{{ $e->phone ?: '—' }}</td>
                        <td class="td text-right font-medium whitespace-nowrap">@money($e->salary)
                            <div class="text-[11px] font-normal text-slate-500">yemek {{ number_format($e->effective_meal_allowance, 0, ',', '.') }} · yol {{ number_format($e->effective_travel_allowance, 0, ',', '.') }}</div></td>
                        @php $lb = ['entitlement' => (float) $e->annual_leave_days, 'used' => (float) ($e->annual_used ?? 0), 'remaining' => round((float) $e->annual_leave_days - (float) ($e->annual_used ?? 0), 1)]; $fmtd = fn ($d) => rtrim(rtrim(number_format((float) $d, 1, ',', '.'), '0'), ','); @endphp
                        <td class="td text-center whitespace-nowrap">
                            <span class="{{ $lb['remaining'] <= 0 ? 'badge-danger' : ($lb['remaining'] <= 3 ? 'badge-warn' : 'badge-ok') }}">{{ $fmtd($lb['remaining']) }} kalan</span>
                            <div class="mt-0.5 text-[11px] text-slate-500">hak {{ $fmtd($lb['entitlement']) }} · kullanılan {{ $fmtd($lb['used']) }}</div>
                        </td>
                        <td class="td whitespace-nowrap">@date($e->hire_date)</td>
                        <td class="td text-center"><x-score-badge :score="$e->avg_score" /></td>
                        <td class="td"><x-status-badge :status="$e->status" /></td>
                        <td class="td text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                            <a href="{{ route('employees.show', $e) }}" class="btn-icon" title="Görüntüle"><i class="fa-regular fa-eye"></i></a>
                            @can('edit')<a href="{{ route('employees.edit', $e) }}" class="btn-icon" title="Düzenle"><i class="fa-solid fa-pen"></i></a>@endcan
                            @can('delete')<x-confirm-form :action="route('employees.destroy', $e)" title="Kaydı sil" :message="$e->full_name.' kaydı silinecek. Ödeme ve değerlendirme geçmişi de silinir.'" title="Sil"><i class="fa-solid fa-trash"></i></x-confirm-form>@endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="px-5 py-12 text-center text-sm text-slate-500">Kayıt bulunamadı.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        {{ $employees->links() }}
    </div>
</x-app-layout>
