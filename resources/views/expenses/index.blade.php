@php
    $months = \App\Models\SalaryPayment::MONTHS;
    $label = $months[$month].' '.$year;
    $cats = \App\Models\Expense::categories();
    $q = ['year' => $year, 'month' => $month];
    $self = route('expenses.index', $q + array_filter(['category' => $category, 'employee_id' => $employeeId]), false);
@endphp
<x-app-layout title="Muhasebe" :subtitle="$label.' · maaşlar, yemek, yol, temizlik ve diğer giderler'">
    <x-slot name="actions">
        <form method="GET" class="flex items-center gap-2">
            @if ($category)<input type="hidden" name="category" value="{{ $category }}">@endif
            @if ($employeeId)<input type="hidden" name="employee_id" value="{{ $employeeId }}">@endif
            <x-ui-select name="month" :search="false" :submit="true" width="auto" class="w-36">
                @foreach ($months as $m => $ml)<option value="{{ $m }}" @selected($m === $month)>{{ $ml }}</option>@endforeach
            </x-ui-select>
            <x-ui-select name="year" :search="false" :submit="true" width="auto" class="w-24">
                @foreach (range(now()->year + 1, now()->year - 3) as $y)<option value="{{ $y }}" @selected($y === $year)>{{ $y }}</option>@endforeach
            </x-ui-select>
        </form>
        @if ($category === 'salary')
        @if ($payrollTotals['count'] > 0)
        <div x-data="{ open: false }">
            <button @click="open = true" class="btn-secondary btn-sm" title="Garanti toplu maaş ödeme dosyası (xlsx)"><i class="fa-solid fa-file-excel"></i> Banka Dosyası</button>
            <template x-teleport="body">
                <div x-show="open" @click.self="open = false" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <form method="GET" action="{{ route('payments.bank-file') }}" class="max-h-[calc(100vh-2rem)] overflow-y-auto w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-pop">
                        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Garanti maaş dosyası</h3><button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                        <p class="text-sm text-slate-600">Bankanın "TGB Yeni Maaş Dosyası" şablonunda Excel indirilir; Garanti internet şubesine doğrudan yüklenir. Kurum / şube / hesap bilgileri <a href="{{ route('settings.edit') }}" class="text-brand-600 hover:underline">Ayarlar</a>'dan gelir.</p>
                        <div><label class="form-label">Kapsam</label>
                            <x-ui-select name="scope" :search="false"><option value="remaining">Sadece kalan tutarı olanlar (kalan kadar)</option><option value="all">Tüm personel (net maaş)</option></x-ui-select></div>
                        <div><label class="form-label">Ödeme tarihi</label><x-date-input name="payment_date" :value="now()" /></div>
                        <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Vazgeç</button><button class="btn-primary" @click="setTimeout(() => open = false, 300)"><i class="fa-solid fa-download"></i> İndir</button></div>
                    </form>
                </div>
            </template>
        </div>
        @endif
        @elseif (auth()->user()->canEdit())
        <div x-data="{ open: false }">
            <button @click="open = true" class="btn-secondary btn-sm" title="Tüm aktif personele bu ayın yemek ücreti ve yol parasını aç"><i class="fa-solid fa-wand-magic-sparkles"></i> Yemek &amp; Yol Oluştur</button>
            <template x-teleport="body">
                <div x-show="open" @click.self="open = false" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <form method="POST" action="{{ route('expenses.generate-allowances') }}" class="max-h-[calc(100vh-2rem)] overflow-y-auto w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-pop">
                        @csrf <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">{{ $label }} yemek &amp; yol</h3><button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                        <p class="text-sm text-slate-600">Tüm aktif personele bu ay için <b>yemek ücreti</b> ve <b>yol parası</b> kaydı açılır. Tutarlar personel kartındaki özel değerden, yoksa <a href="{{ route('settings.edit') }}" class="text-brand-600 hover:underline">Ayarlar</a>'daki standart / emekli tutarlarından alınır. Zaten açılmış olanlar atlanır.</p>
                        <div class="rounded-lg bg-slate-50 px-4 py-2 text-xs text-slate-600">
                            Standart: yemek @money(\App\Models\Setting::amount('meal_allowance')) · yol @money(\App\Models\Setting::amount('travel_allowance'))<br>
                            Emekli: yemek @money(\App\Models\Setting::amount('meal_allowance_retired')) · yol @money(\App\Models\Setting::amount('travel_allowance_retired'))
                        </div>
                        <div><label class="form-label">Kayıt durumu</label><x-ui-select name="status" :search="false"><option value="pending">Bekliyor (ödeyince işaretlerim)</option><option value="paid">Ödendi</option></x-ui-select></div>
                        <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-wand-magic-sparkles"></i> Oluştur</button></div>
                    </form>
                </div>
            </template>
        </div>
        <div x-data="{ open: {{ $errors->any() && old('_form') === 'expense-create' ? 'true' : 'false' }} }">
            <button @click="open = true" class="btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Yeni Kayıt</button>
            <template x-teleport="body">
                <div x-show="open" @click.self="open = false" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                    <form method="POST" action="{{ route('expenses.store') }}" class="max-h-[calc(100vh-2rem)] overflow-y-auto w-full max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-xl">
                        @csrf <input type="hidden" name="_form" value="expense-create">
                        <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Yeni gider / ödeme kaydı</h3><button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                        <div class="grid grid-cols-2 gap-3">
                            <div><label class="form-label">Kategori *</label>
                                <x-ui-select name="category" :search="false">@foreach ($cats as $k => $c)<option value="{{ $k }}" @selected(old('category', $category) === $k)>{{ $c['label'] }}</option>@endforeach</x-ui-select></div>
                            <div><label class="form-label">Tarih *</label><x-date-input name="expense_date" :value="old('expense_date', now()->format('Y-m') === sprintf('%04d-%02d', $year, $month) ? now()->toDateString() : sprintf('%04d-%02d-01', $year, $month))" :required="true" /></div>
                            <div class="col-span-2"><label class="form-label">Personel <span class="font-normal text-slate-400">— birden fazla seçebilirsiniz, her birine ayrı kayıt açılır; genel gider için boş bırakın</span></label>
                                @php $pre = old('employee_ids', ($cats[$category]['employee_based'] ?? false) && $employeeId ? [$employeeId] : []); @endphp
                                <x-ui-select name="employee_ids[]" multiple placeholder="Personel seçin (birden fazla olabilir)">
                                    @foreach ($employees->where('status', 'active') as $e)<option value="{{ $e->id }}" @selected(in_array($e->id, (array) $pre))>{{ $e->full_name }}</option>@endforeach
                                </x-ui-select>
                                <div class="mt-1 flex flex-wrap gap-1.5 text-[11px]">
                                    <button type="button" @click="const ts = $el.closest('form').querySelector('select[name=\'employee_ids[]\']').tomselect; ts.setValue(Object.keys(ts.options))" class="text-brand-600 hover:underline">Tüm aktif personeli seç</button>
                                    <span class="text-slate-300">·</span>
                                    <button type="button" @click="$el.closest('form').querySelector('select[name=\'employee_ids[]\']').tomselect.clear()" class="text-slate-500 hover:underline">Temizle</button>
                                </div>
                            </div>
                            <div class="col-span-2"><label class="form-label">Açıklama</label><input name="description" value="{{ old('description') }}" placeholder="Örn. Eylül yemek ücreti, deterjan alımı" class="form-input"></div>
                            <div><label class="form-label">Tutar (₺) * <span class="font-normal text-slate-400">kişi başı</span></label><input name="amount" value="{{ old('amount') }}" required inputmode="decimal" placeholder="2.500,00" class="form-input text-right"></div>
                            <div><label class="form-label">Kesinti (₺)</label><input name="deduction" value="{{ old('deduction') }}" inputmode="decimal" placeholder="0,00" class="form-input text-right"></div>
                            <div><label class="form-label">Durum *</label>
                                <x-ui-select name="status" :search="false"><option value="pending" @selected(old('status') === 'pending')>Bekliyor</option><option value="paid" @selected(old('status') === 'paid')>Ödendi</option></x-ui-select></div>
                            <div><label class="form-label">Ödeme yöntemi</label>
                                <x-ui-select name="payment_method" :search="false" placeholder="Kategoriye göre"><option value="">Kategoriye göre</option>@foreach (\App\Models\Expense::METHODS as $k => $l)<option value="{{ $k }}" @selected(old('payment_method') === $k)>{{ $l }}</option>@endforeach</x-ui-select></div>
                            <div><label class="form-label">Ödeme tarihi</label><x-date-input name="paid_at" :value="old('paid_at')" /></div>
                            <div class="col-span-2"><label class="form-label">Fiilen ödenen (₺) <span class="font-normal normal-case text-slate-400">— elden fazla verdiyseniz; fark "iade bekleniyor" olur</span></label><input name="paid_amount" value="{{ old('paid_amount') }}" placeholder="Boşsa tutara eşit" class="form-input text-right"></div>
                            <div class="col-span-2"><label class="form-label">İlk not</label><textarea name="note" rows="2" class="form-input" placeholder="İsteğe bağlı; tarih/saat ile kaydedilir">{{ old('note') }}</textarea></div>
                        </div>
                        <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button></div>
                    </form>
                </div>
            </template>
        </div>
        @endif
    </x-slot>

    {{-- Dönem toplamları: maaş + tüm giderler --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="{{ $label }} Toplam Maliyet" :value="number_format($totals['grand'], 2, ',', '.').' ₺'" icon="fa-calculator" :hint="'Maaş '.number_format($totals['payroll'], 0, ',', '.').' ₺ · giderler '.number_format($totals['total'], 0, ',', '.').' ₺'" />
        <x-stat-card label="Toplam Giden (Ödenen)" :value="number_format($totals['grand_paid'], 2, ',', '.').' ₺'" icon="fa-arrow-up-from-bracket" :hint="'Maaş '.number_format($totals['payroll_paid'], 0, ',', '.').' ₺ · giderler '.number_format($totals['paid'], 0, ',', '.').' ₺'" />
        <x-stat-card label="Toplam Kalan (Ödenecek)" :value="number_format($totals['grand_remaining'], 2, ',', '.').' ₺'" icon="fa-hourglass-half" :hint="'Maaş '.number_format($totals['payroll_remaining'], 0, ',', '.').' ₺ · giderler '.number_format($totals['pending'], 0, ',', '.').' ₺'.($totals['refund_pending'] > 0 ? ' · iade bekleyen '.number_format($totals['refund_pending'], 0, ',', '.').' ₺' : '')" />
    </div>

    {{-- Sekmeler --}}
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('expenses.index', $q) }}" class="{{ ! $category && ! $employeeId ? 'chip chip-active' : 'chip' }}"><i class="fa-solid fa-bars"></i> Tüm Giderler <span class="opacity-70">{{ number_format($totals['total'], 0, ',', '.') }}</span></a>
            <a href="{{ route('expenses.index', $q + ['category' => 'salary']) }}" class="{{ $category === 'salary' ? 'chip chip-active' : 'chip' }}"><i class="fa-solid fa-money-bill-transfer"></i> Maaşlar <span class="opacity-70">{{ number_format($totals['payroll'], 0, ',', '.') }}</span></a>
            @foreach ($cats as $k => $c)
                <a href="{{ route('expenses.index', $q + ['category' => $k]) }}" class="{{ $category === $k ? 'chip chip-active' : 'chip' }}"><i class="fa-solid {{ $c['icon'] }}"></i> {{ $c['label'] }} <span class="opacity-70">{{ number_format($byCategory[$k]['total'], 0, ',', '.') }}</span></a>
            @endforeach
        </div>
        @if ($category !== 'salary')
        <form method="GET" class="flex items-center gap-2">
            <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
            @if ($category)<input type="hidden" name="category" value="{{ $category }}">@endif
            <x-ui-select name="employee_id" :submit="true" :clear="true" width="auto" class="w-56" placeholder="Personele göre">
                <option value="">Personele göre</option>
                @foreach ($employees as $e)<option value="{{ $e->id }}" @selected($employeeId === $e->id)>{{ $e->full_name }}</option>@endforeach
            </x-ui-select>
        </form>
        @endif
    </div>

    @if ($category === 'salary')
        @include('payments._section')
    @else
    <div x-data="{
            sel: [],
            ids: {{ $expenses->pluck('id')->toJson() }},
            bulkEdit: false,
            bulkDelete: false,
            get allChecked() { return this.ids.length > 0 && this.sel.length === this.ids.length; },
            toggleAll(on) { this.sel = on ? [...this.ids] : []; },
        }">
        {{-- Kayıt listesi --}}
        <div class="card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full table-hover">
                    <thead><tr>@can('edit')<th class="th w-10"><input type="checkbox" class="row-check" :checked="allChecked" @change="toggleAll($event.target.checked)" title="Tümünü seç"></th>@endcan<th class="th">Personel</th><th class="th">Tarih</th><th class="th">Kategori</th><th class="th">Açıklama</th><th class="th text-right">Tutar</th><th class="th">Durum</th><th class="th">Ödeme</th><th class="th text-right">İşlem</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse ($expenses as $x)
                        @php $meta = $cats[$x->category]; @endphp
                        <tr class="hover:bg-slate-50/70" x-data="{ edit: false, notes: false, noteCount: {{ $x->notes->count() }} }" @notes-count.window="if ($event.detail.key === 'expenses-{{ $x->id }}') noteCount = $event.detail.count">
                            @can('edit')<td class="td"><input type="checkbox" class="row-check" value="{{ $x->id }}" x-model.number="sel"></td>@endcan
                            <td class="td whitespace-nowrap">
                                @if ($x->employee)<a href="{{ route('employees.show', ['employee' => $x->employee, 'tab' => 'expenses']) }}" class="font-medium text-slate-900 hover:text-brand-600">{{ $x->employee->full_name }}</a>
                                <div class="text-[11px] text-slate-500">{{ $x->employee->position }}</div>
                                @else <span class="font-medium text-slate-500">Genel gider</span> @endif
                            </td>
                            <td class="td whitespace-nowrap">{{ $x->expense_date->format('d.m.Y') }}</td>
                            <td class="td whitespace-nowrap"><span class="badge"><i class="fa-solid {{ $meta['icon'] }}"></i>{{ $meta['label'] }}</span></td>
                            <td class="td max-w-[220px] truncate" title="{{ $x->description }}">{{ $x->description ?: '—' }}</td>
                            <td class="td text-right font-semibold whitespace-nowrap">@money($x->net_amount)
                                @if ($x->deduction > 0)<div class="text-[11px] font-normal text-red-600" title="{{ $x->deduction_note }}">{{ number_format($x->amount, 2, ',', '.') }} − kesinti {{ number_format($x->deduction, 2, ',', '.') }}{{ $x->deduction_note ? ' · '.$x->deduction_note : '' }}</div>@endif
                                @if ($x->overpaid > 0)
                                    <div class="mt-0.5 font-normal">
                                        @if ($x->refund_pending > 0)
                                            <span class="badge-warn" title="Ödenen: {{ number_format($x->paid_amount, 2, ',', '.') }} ₺"><i class="fa-solid fa-rotate-left"></i>Fazla @money($x->overpaid) · iade bekleniyor</span>
                                        @else
                                            <span class="badge" title="İade alındı {{ $x->refund_at?->format('d.m.Y') }}"><i class="fa-solid fa-check"></i>Fazla @money($x->overpaid) · iade alındı</span>
                                        @endif
                                    </div>
                                @endif</td>
                            <td class="td whitespace-nowrap">
                                @can('edit')
                                <form method="POST" action="{{ route('expenses.status', $x) }}" class="block w-32">@csrf
                                    <x-ui-select name="status" :search="false" :submit="true" class="status-select status-{{ $x->status }}">
                                        <option value="pending" @selected($x->status === 'pending')>Bekliyor</option>
                                        <option value="paid" @selected($x->status === 'paid')>Ödendi</option>
                                    </x-ui-select>
                                </form>
                                @else<x-status-badge :status="$x->status" />@endcan
                            </td>
                            <td class="td whitespace-nowrap text-xs text-slate-500">
                                @if ($x->paid_at){{ $x->paid_at->format('d.m.Y') }}@if ($x->method_label) · {{ $x->method_label }}@endif
                                @else <span class="text-slate-300">—</span> @endif
                            </td>
                            <td class="td text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                @if ($x->refund_pending > 0 && auth()->user()->canEdit())
                                    <x-confirm-form :action="route('expenses.refund', $x)" method="POST" variant="success" title="İade alındı"
                                        :message="'Fazla ödenen '.number_format($x->refund_pending, 2, ',', '.').' ₺ personelden geri alındı olarak işaretlenecek.'"
                                        button="Evet, iade alındı" title="İade alındı olarak işaretle"><i class="fa-solid fa-rotate-left"></i></x-confirm-form>
                                @endif
                                <button type="button" @click="notes = true" class="btn-icon relative" title="Notlar"><i class="fa-regular fa-note-sticky"></i><span x-show="noteCount > 0" x-text="noteCount" class="absolute -right-0.5 -top-0.5 grid h-4 min-w-4 place-items-center rounded-full bg-brand-600 px-1 text-[10px] font-semibold text-white"></span></button>
                                @can('edit')<button type="button" @click="edit = true" class="btn-icon" title="Düzenle"><i class="fa-solid fa-pen"></i></button>@endcan
                                @can('delete')<x-confirm-form :action="route('expenses.destroy', $x)" title="Kaydı sil" :message="($x->employee?->full_name ?? 'Genel gider').' · '.$meta['label'].' · '.number_format($x->amount, 2, ',', '.').' ₺ kaydı silinecek.'" title="Sil"><i class="fa-solid fa-trash"></i></x-confirm-form>@endcan
                                </div>

                                {{-- Notlar modalı --}}
                                <x-notes-modal show="notes" :model="$x" :action="route('notes.store', ['type' => 'expenses', 'id' => $x->id])" :key="'expenses-'.$x->id"
                                    :title="$x->employee?->full_name ?? 'Genel gider'" :initials="$x->employee ? null : ''" :subtitle="$meta['label'].' · '.number_format($x->amount, 2, ',', '.').' ₺'.($x->description ? ' · '.$x->description : '')" :meta="$x->expense_date->format('d.m.Y')" />

                                {{-- Düzenleme modalı --}}
                                <template x-teleport="body">
                                    <template x-if="edit">
                                    <div x-show="edit" @click.self="edit = false" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                        <form method="POST" action="{{ route('expenses.update', $x) }}"
                                              x-data="{ amount: {{ (float) $x->amount }}, deduction: {{ (float) $x->deduction }}, paid: {{ $x->paid_amount !== null ? (float) $x->paid_amount : $x->net_amount }}, get net() { return Math.max(0, parseMoney(this.amount) - parseMoney(this.deduction)); }, get over() { return Math.max(0, Math.round((parseMoney(this.paid) - this.net) * 100) / 100); } }"
                                              class="max-h-[calc(100vh-2rem)] overflow-y-auto w-full max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-xl text-left">
                                            @csrf @method('PATCH')
                                            <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Kaydı düzenle</h3><button type="button" @click="edit = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div><label class="form-label">Kategori *</label>
                                                    <x-ui-select name="category" :search="false">@foreach ($cats as $k => $c)<option value="{{ $k }}" @selected($x->category === $k)>{{ $c['label'] }}</option>@endforeach</x-ui-select></div>
                                                <div><label class="form-label">Tarih *</label><x-date-input name="expense_date" :value="$x->expense_date" :required="true" /></div>
                                                <div class="col-span-2"><label class="form-label">Personel</label>
                                                    <x-ui-select name="employee_id" placeholder="Genel gider" :clear="true">
                                                        <option value="">Genel gider (personelsiz)</option>
                                                        @foreach ($employees as $e)<option value="{{ $e->id }}" @selected($x->employee_id === $e->id)>{{ $e->full_name }}</option>@endforeach
                                                    </x-ui-select></div>
                                                <div class="col-span-2"><label class="form-label">Açıklama</label><input name="description" value="{{ $x->description }}" class="form-input"></div>
                                                <div><label class="form-label">Tutar (₺) *</label><input name="amount" x-model="amount" required class="form-input text-right"></div>
                                                <div><label class="form-label">Kesinti (₺) <span class="font-normal text-slate-400">− gelmediği günler vb.</span></label><input name="deduction" x-model="deduction" placeholder="0,00" class="form-input text-right"></div>
                                                <div class="col-span-2"><label class="form-label">Kesinti açıklaması</label><input name="deduction_note" value="{{ $x->deduction_note }}" placeholder="Örn. 3 gün gelmedi" class="form-input"></div>
                                                <div class="col-span-2 flex items-center justify-between rounded-lg bg-slate-50 px-4 py-2 text-sm"><span class="text-slate-600">Ödenecek net</span><b x-text="formatMoney(Math.max(0, parseMoney(amount) - parseMoney(deduction)))"></b></div>
                                                <div><label class="form-label">Durum *</label>
                                                    <x-ui-select name="status" :search="false"><option value="pending" @selected($x->status === 'pending')>Bekliyor</option><option value="paid" @selected($x->status === 'paid')>Ödendi</option></x-ui-select></div>
                                                <div><label class="form-label">Ödeme yöntemi</label>
                                                    <x-ui-select name="payment_method" :search="false" placeholder="Kategoriye göre"><option value="">Kategoriye göre</option>@foreach (\App\Models\Expense::METHODS as $k => $l)<option value="{{ $k }}" @selected($x->payment_method === $k)>{{ $l }}</option>@endforeach</x-ui-select></div>
                                                <div><label class="form-label">Ödeme tarihi</label><x-date-input name="paid_at" :value="$x->paid_at" /></div>
                                                <div class="col-span-2"><label class="form-label">Fiilen ödenen (₺) <span class="font-normal normal-case text-slate-400">— elden fazla verdiyseniz buraya yazın</span></label><input name="paid_amount" x-model="paid" placeholder="Boşsa tutara eşit" class="form-input text-right"></div>
                                            </div>
                                            <div x-show="over > 0" x-cloak class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm">
                                                <div class="mb-1 flex items-center justify-between text-amber-800"><span><i class="fa-solid fa-rotate-left mr-1"></i>Fazla ödeme (para üstü)</span><b x-text="formatMoney(over)"></b></div>
                                                <p class="mb-2 text-xs text-amber-700">Kayıt <b>Ödendi</b> olarak işaretlenir; para üstü gelince satırdaki <i class="fa-solid fa-rotate-left"></i> düğmesiyle "iade alındı" deyin.</p>
                                                <div class="grid grid-cols-2 gap-3">
                                                    <div><label class="form-label">İade alınan</label><input name="refund_amount" value="{{ $x->refund_amount > 0 ? number_format($x->refund_amount, 2, ',', '.') : '' }}" placeholder="0,00" class="form-input text-right"></div>
                                                    <div><label class="form-label">İade tarihi</label><x-date-input name="refund_at" :value="$x->refund_at" /></div>
                                                </div>
                                            </div>
                                            <div class="flex justify-end gap-2"><button type="button" @click="edit = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button></div>
                                        </form>
                                    </div>
                                    </template>
                                </template>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ auth()->user()->can('edit') ? 9 : 8 }}" class="px-5 py-12 text-center text-sm text-slate-500">{{ $label }} için bu filtrede kayıt yok. Sağ üstten <b>Yeni Kayıt</b> veya <b>Toplu Kayıt</b> ekleyin.</td></tr>
                    @endforelse
                    </tbody>
                    @if ($expenses->isNotEmpty())
                    <tfoot class="bg-slate-50 text-sm font-semibold"><tr>
                        <td class="px-4 py-3" colspan="{{ auth()->user()->can('edit') ? 5 : 4 }}">Toplam ({{ $expenses->count() }} kayıt)</td>
                        <td class="px-4 py-3 text-right">@money($expenses->sum(fn ($x) => $x->net_amount))</td>
                        <td class="px-4 py-3 text-xs font-normal text-slate-500" colspan="3">Ödenen @money($expenses->where('status', 'paid')->sum(fn ($x) => $x->net_amount)) · Bekleyen @money($expenses->where('status', 'pending')->sum(fn ($x) => $x->net_amount))@if ($expenses->sum('deduction') > 0) · Kesinti @money($expenses->sum('deduction'))@endif</td>
                    </tr></tfoot>
                    @endif
                </table>
            </div>
        </div>

        {{-- Toplu işlem çubuğu son satırları kapatmasın --}}
        <div x-show="sel.length > 0" x-cloak class="h-20"></div>

        @can('edit')
        {{-- Toplu işlem çubuğu: seçim yapılınca ekranın altında belirir --}}
        <template x-teleport="body">
            <div x-show="sel.length > 0" x-cloak x-transition.opacity class="fixed inset-x-0 bottom-0 z-40 flex justify-center p-4">
                <div class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white px-4 py-2.5 shadow-pop">
                    <span class="mr-1 text-sm font-semibold text-slate-900"><span x-text="sel.length"></span> kayıt seçildi</span>
                    <button type="button" @click="sel = []" class="btn-secondary btn-sm"><i class="fa-solid fa-xmark"></i> Seçimi bırak</button>
                    <span class="mx-1 h-6 w-px bg-slate-200"></span>

                    <form method="POST" action="{{ route('expenses.bulk-status') }}">@csrf
                        <input type="hidden" name="status" value="paid">
                        <template x-for="id in sel" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                        <button class="btn-success btn-sm"><i class="fa-solid fa-circle-check"></i> Ödendi yap</button>
                    </form>
                    <form method="POST" action="{{ route('expenses.bulk-status') }}">@csrf
                        <input type="hidden" name="status" value="pending">
                        <template x-for="id in sel" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                        <button class="btn-secondary btn-sm"><i class="fa-solid fa-clock"></i> Bekliyor yap</button>
                    </form>
                    <button type="button" @click="bulkEdit = true" class="btn-primary btn-sm"><i class="fa-solid fa-pen"></i> Toplu düzenle</button>
                    @can('delete')<button type="button" @click="bulkDelete = true" class="btn-danger btn-sm"><i class="fa-solid fa-trash"></i> Sil</button>@endcan
                </div>
            </div>
        </template>

        {{-- Toplu düzenleme penceresi: boş bırakılan alanlar değişmez --}}
        <template x-teleport="body">
            <template x-if="bulkEdit">
            <div x-show="bulkEdit" @click.self="bulkEdit = false" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                <form method="POST" action="{{ route('expenses.bulk-update') }}"
                      class="max-h-[calc(100vh-2rem)] overflow-y-auto w-full max-w-lg space-y-4 rounded-xl bg-white p-6 shadow-xl text-left">
                    @csrf
                    <template x-for="id in sel" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold">Toplu düzenle · <span x-text="sel.length"></span> kayıt</h3>
                        <button type="button" @click="bulkEdit = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
                    </div>
                    <p class="rounded-lg bg-slate-50 px-3 py-2 text-xs text-slate-600"><i class="fa-solid fa-circle-info mr-1 text-slate-400"></i>Sadece doldurduğunuz alanlar değişir; boş bıraktıklarınız her kayıtta olduğu gibi kalır. Tutarlar <b>kişi başı</b>dır.</p>
                    <div class="grid grid-cols-2 gap-3">
                        <div><label class="form-label">Durum</label>
                            <x-ui-select name="status" :search="false" placeholder="Değiştirme"><option value="">Değiştirme</option><option value="pending">Bekliyor</option><option value="paid">Ödendi</option></x-ui-select></div>
                        <div><label class="form-label">Fiilen ödenen (₺)</label><input name="paid_amount" inputmode="decimal" placeholder="Örn. 3.650,00" class="form-input text-right"></div>
                        <div><label class="form-label">Ödeme yöntemi</label>
                            <x-ui-select name="payment_method" :search="false" placeholder="Değiştirme"><option value="">Değiştirme</option>@foreach (\App\Models\Expense::METHODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</x-ui-select></div>
                        <div><label class="form-label">Ödeme tarihi</label><x-date-input name="paid_at" /></div>
                        <div><label class="form-label">Tutar (₺)</label><input name="amount" inputmode="decimal" placeholder="Değiştirme" class="form-input text-right"></div>
                        <div><label class="form-label">Kesinti (₺)</label><input name="deduction" inputmode="decimal" placeholder="Değiştirme" class="form-input text-right"></div>
                        <div><label class="form-label">Kategori</label>
                            <x-ui-select name="category" :search="false" placeholder="Değiştirme"><option value="">Değiştirme</option>@foreach ($cats as $k => $c)<option value="{{ $k }}">{{ $c['label'] }}</option>@endforeach</x-ui-select></div>
                        <div><label class="form-label">Tarih</label><x-date-input name="expense_date" /></div>
                        <div class="col-span-2"><label class="form-label">Açıklama</label><input name="description" placeholder="Değiştirme" class="form-input"></div>
                    </div>
                    <div class="flex justify-end gap-2"><button type="button" @click="bulkEdit = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Seçili kayıtlara uygula</button></div>
                </form>
            </div>
            </template>
        </template>

        @can('delete')
        {{-- Toplu silme onayı --}}
        <template x-teleport="body">
            <template x-if="bulkDelete">
            <div x-show="bulkDelete" @click.self="bulkDelete = false" x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4">
                <form method="POST" action="{{ route('expenses.bulk-destroy') }}" class="max-h-[calc(100vh-2rem)] overflow-y-auto w-full max-w-sm rounded-xl border border-slate-200 bg-white p-6 shadow-xl text-left">
                    @csrf
                    <template x-for="id in sel" :key="id"><input type="hidden" name="ids[]" :value="id"></template>
                    <div class="mb-3 flex items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-red-50 text-red-600"><i class="fa-solid fa-trash"></i></span>
                        <h3 class="text-base font-semibold text-slate-900">Kayıtları sil</h3>
                    </div>
                    <p class="text-sm text-slate-600">Seçili <b x-text="sel.length"></b> kayıt ve notları kalıcı olarak silinecek. Bu işlem geri alınamaz.</p>
                    <div class="mt-4 flex justify-end gap-2"><button type="button" @click="bulkDelete = false" class="btn-secondary">Vazgeç</button><button class="btn-danger"><i class="fa-solid fa-trash"></i> Evet, sil</button></div>
                </form>
            </div>
            </template>
        </template>
        @endcan
        @endcan

    </div>
    @endif
</x-app-layout>
