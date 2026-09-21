@php $p = \App\Support\M2Pricing::class; @endphp
<x-app-layout title="m² Hesaplayıcı" subtitle="Ofis temizlik fiyatı: metrekareyi yazın, tutar çıksın">
    <div x-data="{
            m2: '',
            price: '',
            get n() { return Math.max(0, parseMoney(this.m2)); },
            get manual() { return parseMoney(this.price) > 0; },
            get large() { return this.n > {{ $p::LARGE_LIMIT }}; },
            get small() { return this.n > 0 && this.n < {{ $p::SMALL_LIMIT }}; },
            get autoRate() { return this.large ? {{ $p::LARGE_RATE }} : {{ $p::RATE }}; },
            get rate() { return this.manual ? parseMoney(this.price) : this.autoRate; },
            get total() { return Math.round(this.n * this.rate * 100) / 100; },
         }" class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">

        <div class="card p-6">
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
                    <span x-show="small" x-cloak class="badge-warn"><i class="fa-solid fa-circle-exclamation"></i> 50 m² altı küçük alan</span>
                    <span x-show="manual" x-cloak class="badge"><i class="fa-solid fa-pen"></i> Elle girilen fiyat</span>
                    <span x-show="large && !manual" x-cloak class="badge-info"><i class="fa-solid fa-tag"></i> 100 m² üstü indirimli fiyat</span>
                </div>
            </div>
        </div>

        <div class="card p-6">
            <h2 class="font-semibold text-slate-900"><i class="fa-solid fa-list-ol"></i>Fiyat kuralı</h2>
            <ul class="mt-4 space-y-3 text-sm">
                <li class="flex justify-between gap-3" :class="small ? 'font-semibold text-slate-900' : 'text-slate-600'"><span>50 m² altı</span><span>{{ $p::RATE }} ₺ / m²</span></li>
                <li class="flex justify-between gap-3" :class="n >= {{ $p::SMALL_LIMIT }} && !large ? 'font-semibold text-slate-900' : 'text-slate-600'"><span>50 – 100 m²</span><span>{{ $p::RATE }} ₺ / m²</span></li>
                <li class="flex justify-between gap-3" :class="large ? 'font-semibold text-slate-900' : 'text-slate-600'"><span>100 m² üstü</span><span>{{ $p::LARGE_RATE }} ₺ / m²</span></li>
            </ul>
            <div class="mt-4 border-t border-slate-100 pt-3 text-xs text-slate-500">
                <div class="mb-1.5 font-medium text-slate-600">Örnek</div>
                <ul class="space-y-1">
                    <li class="flex justify-between gap-3"><span>50 m²</span><span>5.000 ₺</span></li>
                    <li class="flex justify-between gap-3"><span>100 m²</span><span>10.000 ₺</span></li>
                    <li class="flex justify-between gap-3"><span>101 m²</span><span>8.080 ₺</span></li>
                </ul>
            </div>
        </div>
    </div>
</x-app-layout>
