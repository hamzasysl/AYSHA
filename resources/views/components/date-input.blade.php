{{-- Flatpickr tarih alanı. value: Y-m-d veya Carbon. Görünen: gg.aa.yyyy --}}
@props(['name', 'value' => null, 'placeholder' => 'gg.aa.yyyy', 'required' => false])
@php
    $v = old($name, $value);
    if ($v instanceof \DateTimeInterface) { $v = $v->format('Y-m-d'); }
@endphp
<div class="relative">
    <input type="text" name="{{ $name }}" value="{{ $v }}" placeholder="{{ $placeholder }}" autocomplete="off"
           x-init="datePicker($el)" @if ($required) required @endif
           {{ $attributes->merge(['class' => 'form-input pr-9']) }}>
    <i class="fa-regular fa-calendar pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
</div>
