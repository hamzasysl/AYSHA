@php $v = fn ($k, $d = null) => old($k, $employee->$k ?? $d); @endphp
<div class="grid gap-6 lg:grid-cols-3">
    <div class="card p-5 space-y-4 lg:col-span-2">
        <h3 class="font-semibold text-slate-900"><i class="fa-solid fa-id-card mr-2 text-slate-400"></i>Kimlik ve İletişim</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label class="form-label">Ad *</label><input name="first_name" value="{{ $v('first_name') }}" required class="form-input"></div>
            <div><label class="form-label">Soyad *</label><input name="last_name" value="{{ $v('last_name') }}" required class="form-input"></div>
            <div><label class="form-label">TC Kimlik No</label><input name="tc_no" value="{{ $v('tc_no') }}" maxlength="11" inputmode="numeric" class="form-input"></div>
            <div><label class="form-label">Doğum Tarihi</label><x-date-input name="birth_date" :value="$employee->birth_date" /></div>
            <div><label class="form-label">Telefon</label><input name="phone" value="{{ $v('phone') }}" placeholder="+90 (5xx) xxx xx xx" class="form-input"></div>
            <div><label class="form-label">E-posta</label><input type="email" name="email" value="{{ $v('email') }}" class="form-input"></div>
            <div class="sm:col-span-2"><label class="form-label">Adres</label><textarea name="address" rows="2" class="form-input">{{ $v('address') }}</textarea></div>
            <div class="sm:col-span-2"><label class="form-label">Acil Durum Kişisi</label><input name="emergency_contact" value="{{ $v('emergency_contact') }}" placeholder="Ad Soyad - Telefon" class="form-input"></div>
        </div>

        <h3 class="pt-2 font-semibold text-slate-900"><i class="fa-solid fa-briefcase mr-2 text-slate-400"></i>İş Bilgileri</h3>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">Görev *</label>
                @php $positions = collect(\App\Models\Employee::positions())->push($v('position'))->filter()->unique(); @endphp
                <x-ui-select name="position" placeholder="Görev seçin veya yazın" data-create="true" required>
                    <option value=""></option>
                    @foreach ($positions as $p)<option value="{{ $p }}" @selected($v('position') === $p)>{{ $p }}</option>@endforeach
                </x-ui-select>
            </div>
            <div>
                <label class="form-label">Durum *</label>
                <x-ui-select name="status" :search="false">
                    @foreach (\App\Models\Employee::STATUSES as $k => $l)<option value="{{ $k }}" @selected($v('status') === $k)>{{ $l }}</option>@endforeach
                </x-ui-select>
            </div>
            <div><label class="form-label">İşe Giriş Tarihi</label><x-date-input name="hire_date" :value="$employee->hire_date" /></div>
            <div><label class="form-label">İşten Ayrılış Tarihi</label><x-date-input name="termination_date" :value="$employee->termination_date" /></div>
            <div>
                <label class="form-label">Yıllık izin hakkı (gün) *</label>
                <input name="annual_leave_days" type="number" min="0" max="365" value="{{ $v('annual_leave_days', 14) }}" required class="form-input text-right">
                <p class="mt-1 text-[11px] text-slate-500">Varsayılan 14. Artan/azalan hakları buradan düzelt.</p>
            </div>
            <div>
                <label class="form-label">Çalışan tipi *</label>
                <x-ui-select name="is_retired" :search="false">
                    <option value="0" @selected(! (int) old('is_retired', $employee->is_retired ? 1 : 0))>Normal</option>
                    <option value="1" @selected((int) old('is_retired', $employee->is_retired ? 1 : 0) === 1)>Emekli</option>
                </x-ui-select>
                <p class="mt-1 text-[11px] text-slate-500">Emekli için Ayarlar'daki emekli yemek / yol tutarları uygulanır.</p>
            </div>
        </div>
    </div>

    <div class="space-y-6">
        <div class="card p-5 space-y-4">
            <h3 class="font-semibold text-slate-900"><i class="fa-solid fa-building-columns mr-2 text-slate-400"></i>Maaş ve Banka</h3>
            <div>
                <label class="form-label">Aylık Net Maaş (₺) *</label>
                <input name="salary" value="{{ $v('salary') !== null && $v('salary') !== '' ? number_format((float) $v('salary'), 2, ',', '.') : '' }}" required inputmode="decimal" placeholder="25.000,00" class="form-input text-right font-medium">
            </div>
            @php
                $std = ['meal' => \App\Models\Setting::amount('meal_allowance'), 'meal_r' => \App\Models\Setting::amount('meal_allowance_retired'), 'travel' => \App\Models\Setting::amount('travel_allowance'), 'travel_r' => \App\Models\Setting::amount('travel_allowance_retired')];
                $fmt = fn ($x) => number_format((float) $x, 2, ',', '.');
            @endphp
            @php
                $retiredNow = (int) old('is_retired', $employee->is_retired ? 1 : 0) === 1;
                $mealVal = $v('meal_allowance') !== null && $v('meal_allowance') !== '' ? $v('meal_allowance') : ($retiredNow ? $std['meal_r'] : $std['meal']);
                $travelVal = $v('travel_allowance') !== null && $v('travel_allowance') !== '' ? $v('travel_allowance') : ($retiredNow ? $std['travel_r'] : $std['travel']);
            @endphp
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="form-label">Yemek ücreti (₺/ay)</label>
                    <input name="meal_allowance" value="{{ $fmt($mealVal) }}" inputmode="decimal" class="form-input text-right">
                    <p class="mt-1 text-[11px] text-slate-500">Standart {{ $fmt($std['meal']) }} · emekli {{ $fmt($std['meal_r']) }}</p>
                </div>
                <div>
                    <label class="form-label">Yol ücreti (₺/ay)</label>
                    <input name="travel_allowance" value="{{ $fmt($travelVal) }}" inputmode="decimal" class="form-input text-right">
                    <p class="mt-1 text-[11px] text-slate-500">Standart {{ $fmt($std['travel']) }} · emekli {{ $fmt($std['travel_r']) }}</p>
                </div>
            </div>
            <div>
                <label class="form-label">Banka</label>
                @php $banks = collect(\App\Models\ListItem::labels('bank'))->push($v('bank_name'))->filter()->unique(); @endphp
                <x-ui-select name="bank_name" placeholder="Banka seçin veya yazın" data-create="true" :clear="true">
                    <option value=""></option>
                    @foreach ($banks as $b)<option value="{{ $b }}" @selected($v('bank_name') === $b)>{{ $b }}</option>@endforeach
                </x-ui-select>
            </div>
            <div class="grid grid-cols-3 gap-3">
                <div><label class="form-label">Banka kodu</label><input name="bank_code" value="{{ $v('bank_code') }}" placeholder="62" class="form-input"></div>
                <div><label class="form-label">Şube kodu</label><input name="branch_code" value="{{ $v('branch_code') }}" placeholder="544" class="form-input"></div>
                <div><label class="form-label">Hesap no</label><input name="account_no" value="{{ $v('account_no') }}" class="form-input"></div>
            </div>
            <div><label class="form-label">IBAN</label><input name="iban" value="{{ $v('iban') }}" placeholder="TR00 0000 0000 0000 0000 0000 00" class="form-input font-mono uppercase"></div>
            <div><label class="form-label">Hesap Sahibi</label><input name="account_holder" value="{{ $v('account_holder') }}" placeholder="Boşsa personelin adı" class="form-input"></div>
        </div>
        @if ($employee->exists)
            <div class="card p-5 text-sm text-slate-600">
                <i class="fa-regular fa-note-sticky mr-1 text-brand-500"></i>
                Notlar personel detay sayfasındaki <a href="{{ route('employees.show', ['employee' => $employee, 'tab' => 'notes']) }}" class="font-medium text-brand-600 hover:underline">Notlar</a> sekmesinden eklenir; her not tarih ve saatiyle saklanır.
            </div>
        @endif
    </div>
</div>
