@php $icons = \App\Models\ExpenseCategory::ICONS; $colors = \App\Models\ExpenseCategory::COLORS; $v = fn ($k, $d = null) => $cat ? old($k, $cat->$k) : old($k, $d); @endphp
<div><label class="form-label">Kategori adı *</label><input name="label" value="{{ $v('label') }}" required maxlength="60" placeholder="Örn. Sağlık Raporu, Üniforma, İkramiye" class="form-input"></div>
<div class="grid grid-cols-2 gap-3">
    <div>
        <label class="form-label">İkon</label>
        <div x-data="{ icon: '{{ $v('icon', 'fa-receipt') }}' }" class="rounded-lg border border-slate-200 p-2">
            <input type="hidden" name="icon" :value="icon">
            <div class="grid grid-cols-5 gap-1">
                @foreach ($icons as $ic)
                    <button type="button" @click="icon = '{{ $ic }}'" :class="icon === '{{ $ic }}' ? 'bg-brand-600 text-white' : 'text-slate-500 hover:bg-slate-100'" class="grid h-8 w-8 place-items-center rounded-md text-sm"><i class="fa-solid {{ $ic }}"></i></button>
                @endforeach
            </div>
        </div>
    </div>
    <div>
        <label class="form-label">Renk</label>
        <div x-data="{ color: '{{ $v('color', 'slate') }}' }" class="rounded-lg border border-slate-200 p-2">
            <input type="hidden" name="color" :value="color">
            <div class="grid grid-cols-4 gap-1.5">
                @foreach ($colors as $ck => $cl)
                    <button type="button" @click="color = '{{ $ck }}'" :class="color === '{{ $ck }}' ? 'ring-2 ring-offset-1 ring-slate-900' : ''" class="h-8 rounded-md bg-{{ $ck }}-500" title="{{ $cl }}"></button>
                @endforeach
            </div>
        </div>
    </div>
</div>
<label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-3 py-2.5 hover:bg-slate-50">
    <input type="hidden" name="employee_based" value="0">
    <input type="checkbox" name="employee_based" value="1" @checked($v('employee_based', true)) class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
    <span><span class="block text-sm font-medium text-slate-800">Personele bağlı ödeme</span><span class="block text-xs text-slate-500">İşaretliyse kayıt bir personele bağlanır ve raporlarda o kişinin Ekstra ödemelerine girer. İşaretsizse genel gider (temizlik malzemesi gibi).</span></span>
</label>
