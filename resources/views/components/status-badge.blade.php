@props(['status'])
@php
    $map = [
        'active'  => ['badge-ok', 'Aktif'],
        'passive' => ['badge', 'Pasif'],
        'paid'    => ['badge-ok', 'Ödendi'],
        'partial' => ['badge-warn', 'Kısmi'],
        'pending' => ['badge-danger', 'Bekliyor'],
    ];
    [$cls, $label] = $map[$status] ?? ['badge', $status];
@endphp
<span {{ $attributes->merge(['class' => $cls]) }}>{{ $label }}</span>
