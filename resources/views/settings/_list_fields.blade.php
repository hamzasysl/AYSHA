@php $v = fn ($k, $d = null) => $item ? old($k, $k === 'label' ? $item->label : ($item->meta[$k] ?? $d)) : old($k, $d); @endphp
<div><label class="form-label">Ad *</label><input name="label" value="{{ $v('label') }}" required maxlength="80" class="form-input" placeholder="{{ $type === 'position' ? 'Örn. Ekip Lideri' : ($type === 'bank' ? 'Örn. Ziraat Bankası' : 'Örn. Babalık izni') }}"></div>
@if ($type === 'leave_type')
    <div>
        <label class="form-label">İkon</label>
        <div x-data="{ icon: '{{ $v('icon', 'fa-calendar') }}' }" class="rounded-lg border border-slate-200 p-2">
            <input type="hidden" name="icon" :value="icon">
            <div class="grid grid-cols-8 gap-1">
                @foreach (['fa-calendar', 'fa-umbrella-beach', 'fa-calendar-minus', 'fa-ring', 'fa-baby', 'fa-hands-praying', 'fa-file-medical', 'fa-user-xmark', 'fa-graduation-cap', 'fa-house-medical', 'fa-plane', 'fa-person-walking', 'fa-heart', 'fa-star', 'fa-clock', 'fa-briefcase'] as $ic)
                    <button type="button" @click="icon = '{{ $ic }}'" :class="icon === '{{ $ic }}' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-100'" class="grid h-8 w-8 place-items-center rounded-md text-sm"><i class="fa-solid {{ $ic }}"></i></button>
                @endforeach
            </div>
        </div>
    </div>
    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 hover:bg-slate-50">
        <input type="hidden" name="deduct" value="0">
        <input type="checkbox" name="deduct" value="1" @checked($v('deduct', false)) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
        <span><span class="block text-sm font-medium text-slate-800">Yıllık izin hakkından düşer</span><span class="block text-xs text-slate-500">İşaretliyse bu türde açılan izin varsayılan olarak yıllık izinden düşülür (kayıtta değiştirilebilir).</span></span>
    </label>
@endif
