@php
    $v = fn ($k, $d = null) => old($k, $leave->$k ?? $d);
    $types = \App\Models\Leave::types();
    $warning = session('leave_warning');
    $dateVal = fn ($x) => $x instanceof \DateTimeInterface ? $x->format('Y-m-d') : $x;
@endphp
<div x-data="{
        balances: @js($balances),
        deductDefaults: @js(collect($types)->map(fn ($t) => $t['deduct'])),
        employee: '{{ $v('employee_id') }}',
        type: '{{ $v('type', 'annual') }}',
        year: '{{ $v('leave_year', now()->year) }}',
        start: '{{ $dateVal($v('start_date')) }}',
        end: '{{ $dateVal($v('end_date')) }}',
        days: '{{ $v('days', 1) }}',
        deduct: {{ $v('deduct_annual', true) ? 'true' : 'false' }},
        auto: {{ old('days') ? 'false' : 'true' }},
        force: {{ $warning ? 'false' : 'false' }},
        editingDays: {{ $leave->exists ? (float) $leave->days : 0 }},
        editingDeduct: {{ $leave->exists && $leave->deduct_annual ? 'true' : 'false' }},
        get remaining() {
            const r = this.balances?.[this.employee]?.[this.year];
            if (r === undefined) return null;
            return this.editingDeduct && String(this.year) === '{{ $leave->leave_year }}' ? r + this.editingDays : r;
        },
        get over() { return this.deduct && this.remaining !== null && parseFloat(String(this.days).replace(',', '.')) > this.remaining; },
        typeChanged() { this.deduct = !!this.deductDefaults[this.type]; },
        businessDays() {
            if (!this.start || !this.end) return 0;
            const s = new Date(this.start), e = new Date(this.end); if (e < s) return 0;
            let n = 0; for (let d = new Date(s); d <= e; d.setDate(d.getDate() + 1)) { const w = d.getDay(); if (w !== 0 && w !== 6) n++; }
            return n;
        },
        recalc() { if (this.auto) this.days = this.businessDays(); }
     }" class="grid gap-6 lg:grid-cols-3">

    <div class="card p-5 space-y-4 lg:col-span-2">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">Personel *</label>
                <x-ui-select name="employee_id" placeholder="Personel seçin..." required x-model="employee" x-init="uiSelect($el); $el.tomselect.on('change', v => employee = v)">
                    <option value="">Personel seçin...</option>
                    @foreach ($employees as $e)<option value="{{ $e->id }}" @selected((int) $v('employee_id') === $e->id)>{{ $e->full_name }}</option>@endforeach
                </x-ui-select>
            </div>
            <div>
                <label class="form-label">İzin nedeni *</label>
                <x-ui-select name="type" :search="false" x-init="uiSelect($el); $el.tomselect.on('change', v => { type = v; typeChanged(); })">
                    @foreach ($types as $k => $t)<option value="{{ $k }}" @selected($v('type', 'annual') === $k)>{{ $t['label'] }}</option>@endforeach
                </x-ui-select>
            </div>
            <div><label class="form-label">İzne çıkış tarihi *</label>
                <x-date-input name="start_date" :value="$v('start_date')" :required="true" x-init="datePicker($el, { onChange: (d, str) => { start = str; if (!end || end < str) end = str; recalc(); } })" /></div>
            <div><label class="form-label">İzin bitiş tarihi *</label>
                <x-date-input name="end_date" :value="$v('end_date')" :required="true" x-init="datePicker($el, { onChange: (d, str) => { end = str; recalc(); } })" /></div>
            <div>
                <label class="form-label">İzin süresi (iş günü) *</label>
                <input name="days" x-model="days" @input="auto = false" inputmode="decimal" required class="form-input text-right font-semibold">
                <p class="mt-1 text-[11px] text-slate-500">Cumartesi–Pazar hariç otomatik hesaplanır (<span x-text="businessDays()"></span>), elle değiştirebilirsiniz.</p>
            </div>
            <div><label class="form-label">İş başı tarihi</label>
                <x-date-input name="return_date" :value="$v('return_date')" placeholder="Boşsa bitişten sonraki ilk iş günü" /></div>
            <div>
                <label class="form-label">İznin ait olduğu yıl *</label>
                <x-ui-select name="leave_year" :search="false" x-init="uiSelect($el); $el.tomselect.on('change', v => year = v)">
                    @foreach (range(now()->year + 1, now()->year - 2) as $y)<option value="{{ $y }}" @selected((int) $v('leave_year', now()->year) === $y)>{{ $y }}</option>@endforeach
                </x-ui-select>
            </div>
            <div><label class="form-label">İzni talep eden</label><input name="requested_by" value="{{ $v('requested_by') }}" placeholder="Ad Soyad" class="form-input"></div>
            <div class="sm:col-span-2">
                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-slate-200 px-4 py-3 hover:bg-slate-50">
                    <input type="hidden" name="deduct_annual" value="0">
                    <input type="checkbox" name="deduct_annual" value="1" x-model="deduct" class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                    <span><span class="block text-sm font-medium text-slate-800">Yıllık izin hakkından düşülsün</span>
                    <span class="block text-xs text-slate-500">Yıllık izinde otomatik işaretli; ücretsiz izin, evlilik, doğum, vefat, rapor ve devamsızlıkta düşmez. İstersen değiştir.</span></span>
                </label>
            </div>
            <div class="sm:col-span-2"><label class="form-label">Not</label><textarea name="note" rows="2" class="form-input" placeholder="Örn. rapor no, gelmeme sebebi, açıklama">{{ $v('note') }}</textarea></div>
        </div>
    </div>

    <div class="space-y-4">
        <div class="card p-5">
            <div class="text-xs font-medium text-slate-500">Yıllık izin durumu</div>
            <template x-if="remaining !== null">
                <div>
                    <div class="mt-1 text-2xl font-semibold tracking-tight" :class="remaining <= 0 ? 'text-red-600' : 'text-slate-900'"><span x-text="remaining"></span> <span class="text-sm font-normal text-slate-500">gün kalan (<span x-text="year"></span>)</span></div>
                    <div class="mt-2 text-xs text-slate-500" x-show="deduct">Bu kayıt sonrası kalan: <b x-text="(remaining - (parseFloat(String(days).replace(',', '.')) || 0)).toFixed(1).replace('.0', '').replace('.', ',')"></b> gün</div>
                </div>
            </template>
            <template x-if="remaining === null"><div class="mt-1 text-sm text-slate-400">Personel seçin.</div></template>
        </div>

        {{-- İzin hakkı yetmiyorsa uyarı + yine de ver --}}
        <div x-show="over" x-cloak class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            <div class="mb-2 font-semibold"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Yıllık izin hakkı yetmiyor</div>
            <p class="text-xs">Kalan <b x-text="remaining"></b> gün, istenen <b x-text="days"></b> gün. Yine de izin vermek istiyor musunuz? Fazlası eksiye düşer ve bakiyede kırmızı görünür.</p>
            <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm font-medium">
                <input type="hidden" name="force" value="0">
                <input type="checkbox" name="force" value="1" x-model="force" class="h-4 w-4 rounded border-amber-300 text-amber-600 focus:ring-amber-500"> Evet, yine de ver
            </label>
        </div>
        @if ($warning)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                <b>{{ $warning['employee'] }}</b> için kalan yıllık izin {{ rtrim(rtrim(number_format($warning['remaining'], 1, ',', ''), '0'), ',') }} gün, istenen {{ rtrim(rtrim(number_format($warning['requested'], 1, ',', ''), '0'), ',') }} gün. Kaydetmek için "Evet, yine de ver" kutusunu işaretleyin.
            </div>
        @endif

        <div class="card p-5 text-xs text-slate-500 space-y-1">
            <div><b class="text-slate-700">Raporlu:</b> sağlık raporu olan günler, yıllık izinden düşmez.</div>
            <div><b class="text-slate-700">Devamsızlık:</b> habersiz gelmeme; gün gün kaydedilir, yıllık izinden düşmez.</div>
            <div><b class="text-slate-700">Yazdır:</b> kayıttan sonra listedeki yazıcı ikonu TTB Grup İzin Formu'nu doldurulmuş açar.</div>
        </div>
    </div>
</div>
