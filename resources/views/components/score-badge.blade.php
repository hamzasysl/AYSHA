@props(['score', 'max' => 10])
@php
    $s = (float) $score;
    $ratio = $max > 0 ? $s / $max : 0;
    $cls = $ratio >= 0.8 ? 'badge-ok' : ($ratio >= 0.6 ? 'badge-warn' : 'badge-danger');
@endphp
@if ($score === null)
    <span {{ $attributes->merge(['class' => 'badge text-slate-400']) }}>—</span>
@else
    <span {{ $attributes->merge(['class' => $cls.' tabular-nums']) }}>{{ rtrim(rtrim(number_format($s, 1, ',', '.'), '0'), ',') }}<span class="text-slate-400">/{{ $max }}</span></span>
@endif
