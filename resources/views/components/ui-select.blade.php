{{-- Tom Select ile sarılmış <select>. Kullanım: <x-ui-select name="status" placeholder="Seçiniz" :search="false"> <option>... </x-ui-select> --}}
@props(['name' => null, 'placeholder' => null, 'search' => true, 'clear' => false, 'submit' => false, 'width' => null])
<select
    @if ($name) name="{{ $name }}" @endif
    x-init="uiSelect($el)"
    data-placeholder="{{ $placeholder }}"
    data-search="{{ $search ? 'true' : 'false' }}"
    data-clear="{{ $clear ? 'true' : 'false' }}"
    @if ($submit) onchange="this.form.submit()" @endif
    {{ $attributes->merge(['class' => $width ? 'w-auto' : 'w-full']) }}
>{{ $slot }}</select>
