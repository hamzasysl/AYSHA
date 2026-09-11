@php $types = \App\Models\Leave::types(); $q = ['year' => $year]; $fmt = fn ($d) => rtrim(rtrim(number_format((float) $d, 1, ',', '.'), '0'), ','); @endphp
<x-app-layout title="İzinler" :subtitle="$year.' · izinler, raporlar ve devamsızlık'">
    <x-slot name="actions">
        <form method="GET" class="flex items-center gap-2">
            @if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif
            @if ($employeeId)<input type="hidden" name="employee_id" value="{{ $employeeId }}">@endif
            <x-ui-select name="year" :search="false" :submit="true" width="auto" class="w-24">
                @foreach (range(now()->year + 1, now()->year - 3) as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach
            </x-ui-select>
        </form>
        @can('edit')<a href="{{ route('leaves.create', $q) }}" class="btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Yeni İzin / Devamsızlık</a>@endcan
    </x-slot>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-3">
            <h2 class="font-semibold text-slate-900"><i class="fa-solid fa-calendar-days mr-2 text-slate-400"></i>İzinler @if ($employeeId)<span class="text-sm font-normal text-slate-500">· {{ $allEmployees->firstWhere('id', $employeeId)?->full_name }} <a href="{{ route('leaves.index', $q + ['type' => $type]) }}" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark"></i></a></span>@endif</h2>
            <div class="flex flex-wrap items-center gap-1.5">
                <a href="{{ route('leaves.index', $q + ['employee_id' => $employeeId]) }}" class="{{ ! $type ? 'chip chip-active' : 'chip' }}">Tümü</a>
                @foreach ($types as $k => $t)
                    <a href="{{ route('leaves.index', $q + ['type' => $k, 'employee_id' => $employeeId]) }}" class="{{ $type === $k ? 'chip chip-active' : 'chip' }}">{{ $t['label'] }}</a>
                @endforeach
                <form method="GET" class="ml-2">
                    <input type="hidden" name="year" value="{{ $year }}">@if ($type)<input type="hidden" name="type" value="{{ $type }}">@endif
                    <x-ui-select name="employee_id" :submit="true" :clear="true" width="auto" class="w-52" placeholder="Personele göre">
                        <option value="">Personele göre</option>
                        @foreach ($allEmployees as $e)<option value="{{ $e->id }}" @selected($employeeId === $e->id)>{{ $e->full_name }}</option>@endforeach
                    </x-ui-select>
                </form>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full table-hover">
                <thead><tr><th class="th">Personel</th><th class="th">Tür</th><th class="th">Tarih</th><th class="th text-center">Gün</th><th class="th text-center">Yıllıktan</th><th class="th">İş başı</th><th class="th">Giren</th><th class="th text-right">İşlem</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($leaves as $l)
                    <tr x-data="{ notes: false, noteCount: {{ $l->notes->count() }} }" @notes-count.window="if ($event.detail.key === 'leaves-{{ $l->id }}') noteCount = $event.detail.count">
                        <td class="td whitespace-nowrap">
                            <a href="{{ route('employees.show', ['employee' => $l->employee, 'tab' => 'leaves']) }}" class="font-medium text-slate-900 hover:text-brand-600">{{ $l->employee->full_name }}</a>
                            <div class="text-[11px] text-slate-500">{{ $l->employee->position }}</div>
                        </td>
                        <td class="td whitespace-nowrap"><span class="badge"><i class="fa-solid {{ $types[$l->type]['icon'] ?? 'fa-calendar' }}"></i>{{ $l->type_label }}</span></td>
                        <td class="td whitespace-nowrap">{{ $l->start_date->format('d.m.Y') }}@if (! $l->start_date->equalTo($l->end_date)) – {{ $l->end_date->format('d.m.Y') }}@endif</td>
                        <td class="td text-center font-semibold">{{ $fmt($l->days) }}</td>
                        <td class="td text-center">@if ($l->deduct_annual)<span class="badge-ok">Düşüldü</span>@else<span class="badge">Düşmedi</span>@endif</td>
                        <td class="td whitespace-nowrap text-slate-500">{{ $l->return_date?->format('d.m.Y') ?? '—' }}</td>
                        <td class="td whitespace-nowrap text-xs text-slate-500">{{ $l->author?->name ?? '—' }}<br>{{ $l->created_at->format('d.m.Y') }}</td>
                        <td class="td text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                                <button type="button" @click="notes = true" class="btn-icon relative" title="Notlar"><i class="fa-regular fa-note-sticky"></i><span x-show="noteCount > 0" x-text="noteCount" class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-brand-600 px-1 text-[10px] font-semibold text-white"></span></button>
                                <a href="{{ route('leaves.print', $l) }}" target="_blank" class="btn-icon" title="İzin formunu yazdır"><i class="fa-solid fa-print"></i></a>
                                @can('edit')<a href="{{ route('leaves.edit', $l) }}" class="btn-icon" title="Düzenle"><i class="fa-solid fa-pen"></i></a>@endcan
                                @can('delete')<x-confirm-form :action="route('leaves.destroy', $l)" title="Kaydı sil" message="Bu izin kaydı silinecek; yıllık izinden düşülmüşse bakiye geri gelir." title="Sil"><i class="fa-solid fa-trash"></i></x-confirm-form>@endcan
                            </div>
                            <x-notes-modal show="notes" :model="$l" :action="route('notes.store', ['type' => 'leaves', 'id' => $l->id])" :key="'leaves-'.$l->id"
                                :title="$l->employee->full_name" :subtitle="$l->type_label.' · '.$fmt($l->days).' iş günü'" :meta="$l->start_date->format('d.m.Y').($l->start_date->equalTo($l->end_date) ? '' : ' – '.$l->end_date->format('d.m.Y'))" />
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-5 py-12 text-center text-sm text-slate-500">{{ $year }} için izin kaydı yok.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
