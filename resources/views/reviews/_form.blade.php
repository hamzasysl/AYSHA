@php $v = fn ($k, $d = null) => old($k, $review->$k ?? $d); @endphp
<div x-data="{ attendance: {{ (int) $v('attendance', 4) }}, quality: {{ (int) $v('quality', 4) }}, attitude: {{ (int) $v('attitude', 4) }}, score: {{ (int) $v('score', 8) }}, auto: {{ $review->exists ? 'false' : 'true' }},
              recalc() { if (this.auto) this.score = Math.max(1, Math.min(10, Math.round((this.attendance + this.quality + this.attitude) / 15 * 10))); } }"
     class="grid gap-6 lg:grid-cols-3">
    <div class="card p-5 space-y-4 lg:col-span-2">
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="form-label">Personel *</label>
                <x-ui-select name="employee_id" placeholder="Personel seçin..." required :disabled="$review->exists">
                    <option value="">Personel seçin...</option>
                    @foreach ($employees as $e)<option value="{{ $e->id }}" @selected((int) $v('employee_id') === $e->id)>{{ $e->full_name }}</option>@endforeach
                </x-ui-select>
                @if ($review->exists)<input type="hidden" name="employee_id" value="{{ $review->employee_id }}">@endif
            </div>
            <div><label class="form-label">Değerlendirme Tarihi *</label><x-date-input name="review_date" :value="$review->review_date" :required="true" /></div>
            <div class="sm:col-span-2">
                <label class="form-label">Değerlendirilen Ay *</label>
                <div class="grid grid-cols-2 gap-3">
                    <x-ui-select name="period_month" :search="false">
                        @foreach (\App\Models\SalaryPayment::MONTHS as $m => $ml)<option value="{{ $m }}" @selected((int) $v('period_month') === $m)>{{ $ml }}</option>@endforeach
                    </x-ui-select>
                    <x-ui-select name="period_year" :search="false">
                        @foreach (range(now()->year + 1, now()->year - 3) as $y)<option value="{{ $y }}" @selected((int) $v('period_year') === $y)>{{ $y }}</option>@endforeach
                    </x-ui-select>
                </div>
                <p class="mt-1 text-[11px] text-slate-500">Her personele her ay için tek değerlendirme girilir; ay başında bir önceki ay puanlanır.</p>
            </div>
        </div>

        <h3 class="pt-2 font-semibold text-slate-900">Kriterler <span class="text-xs font-normal text-slate-500">(1 = zayıf, 5 = mükemmel)</span></h3>
        @foreach (\App\Models\PerformanceReview::CRITERIA as $key => $label)
            <div class="flex items-center gap-4">
                <div class="w-40 text-sm font-medium text-slate-700">{{ $label }}</div>
                <div class="flex gap-1">
                    @for ($i = 1; $i <= 5; $i++)
                        <button type="button" @click="{{ $key }} = {{ $i }}; recalc()" :class="{{ $key }} >= {{ $i }} ? 'bg-amber-400 text-white' : 'bg-slate-100 text-slate-400 hover:bg-slate-200'"
                                class="grid h-9 w-9 place-items-center rounded-lg text-sm font-bold transition">{{ $i }}</button>
                    @endfor
                </div>
                <input type="hidden" name="{{ $key }}" :value="{{ $key }}">
            </div>
        @endforeach

        <div class="grid gap-4 sm:grid-cols-2 pt-2">
            <div><label class="form-label">Güçlü Yönler</label><textarea name="strengths" rows="3" class="form-input" placeholder="Neyi iyi yapıyor?">{{ $v('strengths') }}</textarea></div>
            <div><label class="form-label">Gelişim Alanları</label><textarea name="improvements" rows="3" class="form-input" placeholder="Neyi geliştirmeli?">{{ $v('improvements') }}</textarea></div>
            <div class="sm:col-span-2"><label class="form-label">Ek Not</label><textarea name="notes" rows="2" class="form-input">{{ $v('notes') }}</textarea></div>
        </div>
    </div>

    <div class="card p-5 space-y-4">
        <h3 class="font-semibold text-slate-900">Genel Puan</h3>
        <div class="text-center">
            <div class="text-6xl font-bold" :class="score >= 8 ? 'text-emerald-600' : (score >= 6 ? 'text-amber-600' : 'text-red-600')" x-text="score"></div>
            <div class="text-sm text-slate-500">/ 10</div>
        </div>
        <input type="range" name="score" min="1" max="10" x-model.number="score" @input="auto = false" class="w-full accent-brand-600">
        <label class="flex items-center gap-2 text-xs text-slate-600"><input type="checkbox" x-model="auto" @change="recalc()" class="rounded border-slate-300 text-brand-600"> Kriterlerden otomatik hesapla</label>
        <p class="text-xs text-slate-500">8-10 çok iyi, 6-7 yeterli, 1-5 dikkat gerektirir.</p>
    </div>
</div>
