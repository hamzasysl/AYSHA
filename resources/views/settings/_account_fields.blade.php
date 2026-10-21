{{-- Banka hesabı formu alanları. $acc: düzenlenen hesap ya da null --}}
<div class="grid grid-cols-2 gap-3">
    <div class="col-span-2"><label class="form-label">Kullanım amacı *</label>
        <input name="label" value="{{ old('label', $acc?->label) }}" required placeholder="Örn. KDV dahil tahsilat / KDV hariç tahsilat" class="form-input"></div>
    <div><label class="form-label">Banka</label>
        <x-ui-select name="bank_name" placeholder="Seçiniz" :clear="true" data-create="true">
            <option value="">—</option>
            @foreach (\App\Models\ListItem::labels('bank') as $b)<option value="{{ $b }}" @selected(old('bank_name', $acc?->bank_name) === $b)>{{ $b }}</option>@endforeach
            @if ($acc?->bank_name && ! in_array($acc->bank_name, \App\Models\ListItem::labels('bank'), true))<option value="{{ $acc->bank_name }}" selected>{{ $acc->bank_name }}</option>@endif
        </x-ui-select></div>
    <div><label class="form-label">Hesap sahibi</label><input name="account_holder" value="{{ old('account_holder', $acc?->account_holder ?? \App\Support\Brand::name()) }}" class="form-input"></div>
    <div class="col-span-2"><label class="form-label">IBAN</label><input name="iban" value="{{ old('iban', $acc?->iban_pretty) }}" placeholder="TR00 0000 0000 0000 0000 0000 00" class="form-input font-mono"></div>
    <div><label class="form-label">Hesap no</label><input name="account_no" value="{{ old('account_no', $acc?->account_no) }}" class="form-input font-mono"></div>
    <div><label class="form-label">Şube kodu</label><input name="branch_code" value="{{ old('branch_code', $acc?->branch_code) }}" class="form-input font-mono"></div>
    <div class="col-span-2"><label class="form-label">Açıklama</label><input name="note" value="{{ old('note', $acc?->note) }}" placeholder="İsteğe bağlı" class="form-input"></div>

    <div class="col-span-2 rounded-xl border border-slate-200 bg-slate-50 p-4">
        <label class="flex items-start gap-3 text-sm">
            <input type="checkbox" name="is_payroll" value="1" @checked(old('is_payroll', $acc?->is_payroll)) class="row-check mt-0.5">
            <span><b class="text-slate-800">Maaş ödeme hesabı</b><span class="block text-xs text-slate-500">Muhasebe &gt; Maaşlar'daki Garanti banka dosyası bu hesapla üretilir. Tek hesap işaretlenebilir.</span></span>
        </label>
        <div class="mt-3"><label class="form-label">Kurum kodu <span class="font-normal text-slate-400">— Garanti maaş dosyası için</span></label>
            <input name="corp_code" value="{{ old('corp_code', $acc?->corp_code) }}" placeholder="615399" class="form-input max-w-[200px] font-mono"></div>
    </div>
</div>
