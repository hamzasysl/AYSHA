{{-- Anlaşma formu alanları. $a: düzenlenen anlaşma ya da null --}}
@php
    $d = \App\Http\Controllers\AgreementController::defaults();
    $r = \App\Support\M2Pricing::rates();
    $f = fn ($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
@endphp
<div x-data="{
        m2: '{{ $f($a?->m2) }}',
        rate: '{{ $f($a?->m2_rate ?? $d['m2_rate']) }}',
        rateTouched: {{ $a && $a->m2_rate !== null ? 'true' : 'false' }},
        amountTouched: false,
        money(v) { return new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v || 0); },
        syncAmount() {
            if (! this.amountTouched && this.auto) this.amount = this.money(parseMoney(this.m2) * parseMoney(this.rate));
            this.calc('amount');
        },
        /** Tutarın KDV hariç ve KDV dahil hâlleri birbirini doldurur; hangisi yazılırsa diğeri hesaplanır. */
        calc(src) {
            const k = 1 + parseMoney(this.vat) / 100;
            let net = src === 'gross' ? parseMoney(this.gross) / k : parseMoney(this.amount);
            if (! isFinite(net) || net < 0) net = 0;
            if (src !== 'amount') this.amount = net ? this.money(net) : '';
            if (src !== 'gross') this.gross = net ? this.money(net * k) : '';
        },
        get autoRate() { return parseMoney(this.m2) > {{ $r['large_limit'] }} ? {{ $r['large_rate'] }} : {{ $r['rate'] }}; },
        get large() { return parseMoney(this.m2) > {{ $r['large_limit'] }}; },
        syncRate() { if (! this.rateTouched) this.rate = String(this.autoRate).replace('.', ','); },
        amount: '{{ $f($a?->amount) }}',
        gross: '{{ $a && $a->amount > 0 ? $f($a->gross_total) : '' }}',
        vat: '{{ $f($a?->vat_rate ?? $d['vat_rate']) }}',
        get auto() { return parseMoney(this.m2) > 0 && parseMoney(this.rate) > 0; },
        get net() { return parseMoney(this.amount); },
        get vatAmount() { return Math.round((parseMoney(this.gross) - this.net) * 100) / 100; },
     }" class="grid grid-cols-2 gap-3">

    <div class="col-span-2"><label class="form-label">Şirket adı *</label><input name="name" value="{{ old('name', $a?->name) }}" required placeholder="Örn. Aselsan Genel Müdürlük" class="form-input"></div>

    <div><label class="form-label">Tür *</label>
        <x-ui-select name="type" :search="false">@foreach (\App\Models\Agreement::TYPES as $k => $l)<option value="{{ $k }}" @selected(old('type', $a?->type ?? 'recurring') === $k)>{{ $l }}</option>@endforeach</x-ui-select></div>
    <div><label class="form-label">Durum *</label>
        <x-ui-select name="status" :search="false">@foreach (\App\Models\Agreement::STATUSES as $k => $l)<option value="{{ $k }}" @selected(old('status', $a?->status ?? 'active') === $k)>{{ $l }}</option>@endforeach</x-ui-select></div>

    <div><label class="form-label">Alan (m²)</label><input name="m2" x-model="m2" @input="syncRate(); syncAmount()" inputmode="decimal" placeholder="Örn. 450" class="form-input text-right"></div>
    <div><label class="form-label">m² fiyatı (KDV hariç)</label>
        <input name="m2_rate" x-model="rate" @input="rateTouched = true; syncAmount()" inputmode="decimal" class="form-input text-right">
        <p class="mt-1 flex items-center gap-2 text-[11px]">
            <span x-show="! rateTouched && large" x-cloak class="text-brand-600">{{ $f($r['large_limit']) }} m² üstü fiyatı uygulandı</span>
            <span x-show="! rateTouched && ! large" x-cloak class="text-slate-400">Ayarlardaki standart fiyat</span>
            <button type="button" x-show="rateTouched" x-cloak @click="rateTouched = false; syncRate(); syncAmount()" class="font-medium text-brand-600 hover:underline"><i class="fa-solid fa-rotate-left"></i> Kurala göre</button>
        </p>
    </div>

    <div class="col-span-2 grid grid-cols-3 gap-3">
        <div><label class="form-label">Tutar <span class="font-normal text-slate-400">KDV hariç</span> *</label>
            <input name="amount" x-model="amount" @input="amountTouched = true; calc('amount')" inputmode="decimal" required class="form-input text-right"></div>
        <div><label class="form-label">Tutar <span class="font-normal text-slate-400">KDV dahil</span></label>
            <input x-model="gross" @input="amountTouched = true; calc('gross')" inputmode="decimal" class="form-input text-right"></div>
        <div><label class="form-label">KDV oranı (%)</label>
            <input name="vat_rate" x-model="vat" @input="calc('amount')" inputmode="decimal" class="form-input text-right"></div>

        <p class="col-span-3 -mt-1 flex items-center gap-2 text-[11px]">
            <span x-show="! amountTouched && auto" x-cloak class="text-slate-400">m² × m² fiyatından hesaplandı; iki tutardan hangisini yazarsanız diğeri hesaplanır.</span>
            <span x-show="amountTouched" x-cloak class="text-slate-500">KDV <b class="tabular-nums text-slate-700" x-text="formatMoney(vatAmount)"></b></span>
            <button type="button" x-show="amountTouched && auto" x-cloak @click="amountTouched = false; syncAmount()" class="font-medium text-brand-600 hover:underline"><i class="fa-solid fa-rotate-left"></i> m²'den hesapla</button>
        </p>
    </div>

    <div><label class="form-label">Yetkili</label><input name="contact_name" value="{{ old('contact_name', $a?->contact_name) }}" class="form-input"></div>
    <div><label class="form-label">Telefon</label><input name="phone" value="{{ old('phone', $a?->phone) }}" placeholder="0532 000 0000" class="form-input"></div>
    <div class="col-span-2"><label class="form-label">E-posta</label><input name="email" value="{{ old('email', $a?->email) }}" class="form-input"></div>
    <div><label class="form-label">Başlangıç</label><x-date-input name="start_date" :value="old('start_date', $a?->start_date)" /></div>
    <div><label class="form-label">Bitiş</label><x-date-input name="end_date" :value="old('end_date', $a?->end_date)" /></div>
    <div class="col-span-2"><label class="form-label">Konum <span class="font-normal text-slate-400">— bina, kat, kapı no</span></label><input name="address" value="{{ old('address', $a?->address) }}" placeholder="Örn. 1. blok, 3. kat, no 129" class="form-input"></div>
    <div class="col-span-2"><label class="form-label">Açıklama</label><input name="note" value="{{ old('note', $a?->note) }}" placeholder="Haftada 3 gün, 2 personel vb." class="form-input"></div>
</div>
