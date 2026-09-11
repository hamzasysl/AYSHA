@props(['retired' => false])
<span {{ $attributes->merge(['class' => $retired ? 'badge-warn' : 'badge-info']) }}>{{ $retired ? 'Emekli' : 'Normal' }}</span>
