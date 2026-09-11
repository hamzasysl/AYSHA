@props(['label', 'value', 'icon' => 'fa-chart-simple', 'color' => 'brand', 'hint' => null])
@php
    $map = [
        'brand' => ['border-brand-200', 'bg-brand-50 text-brand-600'],
        'sky' => ['border-sky-200', 'bg-sky-50 text-sky-600'],
        'emerald' => ['border-emerald-200', 'bg-emerald-50 text-emerald-600'],
        'amber' => ['border-amber-200', 'bg-amber-50 text-amber-600'],
        'red' => ['border-red-200', 'bg-red-50 text-red-500'],
        'violet' => ['border-violet-200', 'bg-violet-50 text-violet-600'],
        'slate' => ['border-slate-200', 'bg-slate-100 text-slate-500'],
        'indigo' => ['border-brand-200', 'bg-brand-50 text-brand-600'],
        'gray' => ['border-slate-200', 'bg-slate-100 text-slate-500'],
    ];
    [$border, $tile] = $map[$color] ?? $map['brand'];
@endphp
<div {{ $attributes->merge(['class' => "card {$border} min-w-0 p-3.5"]) }}>
    <div class="grid h-8 w-8 place-items-center rounded-lg text-xs {{ $tile }}"><i class="fa-solid {{ $icon }}"></i></div>
    <div class="mt-2.5 truncate text-base font-bold tracking-tight text-slate-900">{{ $value }}</div>
    <div class="mt-0.5 truncate text-[11px] font-medium text-slate-600">{{ $label }}</div>
    @if ($hint)<div class="mt-0.5 truncate text-[10.5px] text-slate-400">{{ $hint }}</div>@endif
</div>
