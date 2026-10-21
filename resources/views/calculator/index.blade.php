@php
    $r = \App\Support\M2Pricing::rates();
    $n = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', '.'), '0'), ',');
    $vat = \App\Models\Setting::amount('vat_rate');
    $examples = collect([$r['small_limit'], $r['large_limit'], $r['large_limit'] + 1])->filter(fn ($v) => $v > 0)->unique()
        ->map(fn ($v) => [$n($v), \App\Support\M2Pricing::calculate($v)['total']]);
@endphp
<x-app-layout title="Hesaplayıcı" subtitle="Ofis temizlik fiyatı ve KDV hesabı">
    @can('manage')
        <x-slot:actions><a href="{{ route('settings.edit', ['tab' => 'pricing']) }}" class="btn-secondary btn-sm"><i class="fa-solid fa-sliders"></i> Fiyatları düzenle</a></x-slot:actions>
    @endcan
    <div x-data="{
            m2: '',
            price: '',
            get n() { return Math.max(0, parseMoney(this.m2)); },
            get manual() { return parseMoney(this.price) > 0; },
            get large() { return this.n > {{ $r['large_limit'] }}; },
            get small() { return this.n > 0 && this.n < {{ $r['small_limit'] }}; },
            get autoRate() { return this.large ? {{ $r['large_rate'] }} : {{ $r['rate'] }}; },
            get rate() { return this.manual ? parseMoney(this.price) : this.autoRate; },
            get total() { return Math.round(this.n * this.rate * 100) / 100; },
         }" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">

        <div class="card p-6">
            <h2 class="mb-4 font-semibold text-slate-900"><i class="fa-solid fa-ruler-combined"></i>m² Hesaplama</h2>
            <label class="form-label" for="m2">Alan (m²)</label>
            <div class="relative">
                <input id="m2" x-model="m2" x-init="$el.focus()" inputmode="decimal" placeholder="Örn. 120" class="form-input h-12 pr-12 text-right text-lg font-semibold">
                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm text-slate-400">m²</span>
            </div>

            <label class="form-label mt-4" for="price">m² başı fiyat <span class="font-normal text-slate-400">— boşsa kurala göre</span></label>
            <div class="relative">
                <input id="price" x-model="price" inputmode="decimal" :placeholder="autoRate + ' (otomatik)'" class="form-input h-12 pr-16 text-right text-lg font-semibold">
                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm text-slate-400">₺ / m²</span>
            </div>
            <button type="button" x-show="manual" x-cloak @click="price = ''" class="mt-2 text-xs font-medium text-brand-600 hover:underline"><i class="fa-solid fa-rotate-left"></i> Kurala göre hesapla</button>

            <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50 p-5">
                <div class="text-xs font-medium text-slate-500">Temizlik ücreti</div>
                <div class="mt-1 text-3xl font-bold tracking-tight text-slate-900" x-text="formatMoney(total)"></div>
                <div class="mt-1 text-sm text-slate-500" x-show="n > 0" x-cloak>
                    <span x-text="n.toLocaleString('tr-TR')"></span> m² × <span x-text="rate.toLocaleString('tr-TR')"></span> ₺
                </div>
                <div class="mt-3 flex flex-wrap gap-2">
                    <span x-show="small" x-cloak class="badge-warn"><i class="fa-solid fa-circle-exclamation"></i> {{ $n($r['small_limit']) }} m² altı küçük alan</span>
                    <span x-show="manual" x-cloak class="badge"><i class="fa-solid fa-pen"></i> Elle girilen fiyat</span>
                    <span x-show="large && !manual" x-cloak class="badge-info"><i class="fa-solid fa-tag"></i> {{ $n($r['large_limit']) }} m² üstü indirimli fiyat</span>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-semibold text-slate-900"><i class="fa-solid fa-list-ol"></i>Fiyat kuralı</h2>
            <ul class="mt-4 space-y-3 text-sm">
                <li class="flex justify-between gap-3" :class="small ? 'font-semibold text-slate-900' : 'text-slate-600'"><span>{{ $n($r['small_limit']) }} m² altı</span><span>{{ $n($r['rate']) }} ₺ / m²</span></li>
                <li class="flex justify-between gap-3" :class="n >= {{ $r['small_limit'] }} && !large ? 'font-semibold text-slate-900' : 'text-slate-600'"><span>{{ $n($r['small_limit']) }} – {{ $n($r['large_limit']) }} m²</span><span>{{ $n($r['rate']) }} ₺ / m²</span></li>
                <li class="flex justify-between gap-3" :class="large ? 'font-semibold text-slate-900' : 'text-slate-600'"><span>{{ $n($r['large_limit']) }} m² üstü</span><span>{{ $n($r['large_rate']) }} ₺ / m²</span></li>
            </ul>
            <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                <div class="mb-1.5 font-medium text-slate-600">Örnek</div>
                <ul class="space-y-1">
                    @foreach ($examples as [$m, $total])
                        <li class="flex justify-between gap-3"><span>{{ $m }} m²</span><span>@money($total)</span></li>
                    @endforeach
                </ul>
            </div>
        </div>

        <div class="card col-span-full p-6" x-data="{
                rate: '{{ $n($vat) }}',
                discount: '',
                net: '', gross: '', total: '', base: 0,
                fmt(v) { return v ? new Intl.NumberFormat('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(v) : ''; },
                get r() { return Math.max(0, parseMoney(this.rate)) / 100; },
                get d() { return Math.min(100, Math.max(0, parseMoney(this.discount))) / 100; },
                calc(src) {
                    const k = 1 + this.r, ind = 1 - this.d;
                    let n = src === 'net' ? parseMoney(this.net)
                          : src === 'gross' ? parseMoney(this.gross) / k
                          : (ind > 0 ? parseMoney(this.total) / k / ind : 0);
                    if (! isFinite(n) || n < 0) n = 0;
                    this.base = n;
                    if (src !== 'net') this.net = this.fmt(n);
                    if (src !== 'gross') this.gross = this.fmt(n * k);
                    if (src !== 'total') this.total = this.fmt(n * ind * k);
                },
                get discountAmount() { return this.base * this.d; },
                get matrah() { return this.base - this.discountAmount; },
                get vat() { return this.matrah * this.r; },
             }">
            <h2 class="mb-1 font-semibold text-slate-900"><i class="fa-solid fa-percent"></i>KDV Hesaplama</h2>
            <p class="mb-4 text-xs text-slate-500">Fiyat alanlarından hangisini yazarsanız diğerleri hesaplanır. İndirim, KDV'den önce fiyattan düşülür.</p>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div>
                    <label class="form-label" for="kdv_net">Birim fiyat <span class="font-normal text-slate-400">KDV hariç</span></label>
                    <div class="relative"><input id="kdv_net" x-model="net" @input="calc('net')" inputmode="decimal" placeholder="0,00" class="form-input pr-8 text-right font-medium tabular-nums"><span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">₺</span></div>
                </div>
                <div>
                    <label class="form-label" for="kdv_gross">Birim fiyat <span class="font-normal text-slate-400">KDV dahil</span></label>
                    <div class="relative"><input id="kdv_gross" x-model="gross" @input="calc('gross')" inputmode="decimal" placeholder="0,00" class="form-input pr-8 text-right font-medium tabular-nums"><span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">₺</span></div>
                </div>
                <div>
                    <label class="form-label" for="kdv_rate">KDV oranı</label>
                    <div class="relative"><input id="kdv_rate" x-model="rate" @input="calc('net')" inputmode="decimal" class="form-input pr-8 text-right font-medium tabular-nums"><span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">%</span></div>
                </div>
                <div>
                    <label class="form-label" for="kdv_discount">İndirim</label>
                    <div class="relative"><input id="kdv_discount" x-model="discount" @input="calc('net')" inputmode="decimal" placeholder="0" class="form-input pr-8 text-right font-medium tabular-nums"><span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">%</span></div>
                </div>
                <div>
                    <label class="form-label" for="kdv_total">Ödenecek tutar <span class="font-normal text-slate-400">KDV dahil</span></label>
                    <div class="relative"><input id="kdv_total" x-model="total" @input="calc('total')" inputmode="decimal" placeholder="0,00" class="form-input pr-8 text-right font-bold tabular-nums"><span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">₺</span></div>
                </div>
            </div>

            <div class="mt-5 grid gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 sm:grid-cols-4">
                <div x-show="d > 0" x-cloak><div class="text-xs text-slate-500">İndirim (%<span x-text="discount || 0"></span>)</div><div class="mt-0.5 font-semibold tabular-nums text-red-600" x-text="'−' + formatMoney(discountAmount)"></div></div>
                <div><div class="text-xs text-slate-500">Matrah (KDV hariç)</div><div class="mt-0.5 font-semibold tabular-nums text-slate-900" x-text="formatMoney(matrah)"></div></div>
                <div><div class="text-xs text-slate-500">KDV (%<span x-text="rate || 0"></span>)</div><div class="mt-0.5 font-semibold tabular-nums text-brand-600" x-text="formatMoney(vat)"></div></div>
                <div><div class="text-xs text-slate-500">Genel toplam</div><div class="mt-0.5 text-lg font-bold tabular-nums text-slate-900" x-text="formatMoney(matrah + vat)"></div></div>
            </div>
        </div>
    </div>
</x-app-layout>
