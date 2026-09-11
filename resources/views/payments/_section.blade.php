{{-- Muhasebe > Maaşlar sekmesi: dönem bordrosu. Beklenen değişkenler: $payments, $payrollTotals, $missing, $year, $month, $status, $label --}}
<div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
    <x-stat-card label="Bordro Net Toplam" :value="number_format($payrollTotals['net'], 2, ',', '.').' ₺'" icon="fa-file-invoice-dollar" :hint="$payrollTotals['count'].' personel'" />
    <x-stat-card label="Ödenen Maaş" :value="number_format($payrollTotals['paid'], 2, ',', '.').' ₺'" icon="fa-circle-check" :hint="$payrollTotals['paid_count'].' tamamlandı'" />
    <x-stat-card label="Kalan Maaş" :value="number_format($payrollTotals['remaining'], 2, ',', '.').' ₺'" icon="fa-hourglass-half" :hint="$payrollTotals['pending_count'].' bekliyor, '.$payrollTotals['partial_count'].' kısmi'.($payrollTotals['refund_pending'] > 0 ? ' · iade bekleyen '.number_format($payrollTotals['refund_pending'], 2, ',', '.').' ₺' : '')" />
        <div class="card p-5 flex flex-col justify-center gap-2 {{ auth()->user()->canEdit() ? '' : 'hidden' }}">
            @if ($payrollTotals['count'] === 0)
                <form method="POST" action="{{ route('payments.generate') }}">@csrf
                    <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                    <button class="btn-primary w-full"><i class="fa-solid fa-wand-magic-sparkles"></i> {{ $label }} bordrosunu oluştur</button>
                </form>
                <p class="text-center text-[11px] text-slate-500">Tüm aktif personele maaşı kadar bekleyen kayıt açar.</p>
            @else
                @if ($payrollTotals['remaining'] > 0)
                    <div x-data="{ open: false }">
                        <button @click="open = true" class="btn-success w-full"><i class="fa-solid fa-check-double"></i> Tümünü ödendi işaretle</button>
                        <template x-teleport="body">
                            <div x-show="open" @click.self="open = false" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                                <form method="POST" action="{{ route('payments.mark-all-paid') }}" class="w-full max-w-sm rounded-xl bg-white p-6 shadow-xl space-y-4">
                                    @csrf <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                                    <h3 class="text-lg font-semibold">Toplu ödeme</h3>
                                    <p class="text-sm text-slate-600">{{ $label }} döneminde bekleyen <b>{{ $payrollTotals['pending_count'] + $payrollTotals['partial_count'] }}</b> kaydın tamamı (<b>@money($payrollTotals['remaining'])</b>) bugün ödendi olarak işaretlenecek.</p>
                                    <div><label class="form-label">Ödeme yöntemi</label><x-ui-select name="payment_method" :search="false"><option value="transfer">Havale / EFT</option><option value="cash">Nakit</option></x-ui-select></div>
                                    <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Vazgeç</button><button class="btn-success">Onayla</button></div>
                                </form>
                            </div>
                        </template>
                    </div>
                @else
                    <div class="text-center text-sm font-medium text-emerald-700"><i class="fa-solid fa-circle-check mr-1"></i> Bu dönem tamamlandı</div>
                @endif
                @if ($missing->isNotEmpty())
                    <form method="POST" action="{{ route('payments.generate') }}">@csrf
                        <input type="hidden" name="year" value="{{ $year }}"><input type="hidden" name="month" value="{{ $month }}">
                        <button class="btn-secondary btn-sm w-full"><i class="fa-solid fa-user-plus"></i> Eksik {{ $missing->count() }} personeli ekle</button>
                    </form>
                @endif
            @endif
        </div>
</div>

    @if ($payrollTotals['count'] > 0)
    <div class="flex flex-wrap gap-2">
        @php $chip = fn ($active, $color = null) => $active ? "chip chip-active" : "chip"; $sq = ['category' => 'salary', 'year' => $year, 'month' => $month]; @endphp
        <a href="{{ route('expenses.index', $sq) }}" class="{{ $chip(! $status, 'brand') }}"><i class="fa-solid fa-bars"></i> Tümü <b>({{ $payrollTotals['count'] }})</b></a>
        <a href="{{ route('expenses.index', $sq + ['status' => 'pending']) }}" class="{{ $chip($status === 'pending', 'red') }}"><i class="fa-solid fa-clock"></i> Bekleyen <b>({{ $payrollTotals['pending_count'] }})</b></a>
        <a href="{{ route('expenses.index', $sq + ['status' => 'partial']) }}" class="{{ $chip($status === 'partial', 'amber') }}"><i class="fa-solid fa-circle-half-stroke"></i> Kısmi <b>({{ $payrollTotals['partial_count'] }})</b></a>
        <a href="{{ route('expenses.index', $sq + ['status' => 'paid']) }}" class="{{ $chip($status === 'paid', 'emerald') }}"><i class="fa-solid fa-circle-check"></i> Ödenen <b>({{ $payrollTotals['paid_count'] }})</b></a>
    </div>
    @endif

    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full table-hover">
                <thead><tr>
                    <th class="th">Personel</th><th class="th">IBAN</th><th class="th text-right">Maaş</th><th class="th text-right">Prim</th>
                    <th class="th text-right">Avans</th><th class="th text-right">Kesinti</th><th class="th text-right">Net</th><th class="th text-right">Ödenen</th>
                    <th class="th">Durum</th><th class="th">Ödeme</th><th class="th text-right">İşlem</th>
                </tr></thead>
                <tbody class="divide-y divide-slate-100">
                @forelse ($payments as $p)
                    <tr class="hover:bg-slate-50/70" x-data="{ edit: false }">
                        <td class="td whitespace-nowrap">
                            <a href="{{ route('employees.show', ['employee' => $p->employee, 'tab' => 'payments']) }}" class="font-medium text-slate-900 hover:text-brand-600">{{ $p->employee->full_name }}</a>
                            <div class="text-[11px] text-slate-500">{{ $p->employee->position }}</div>
                        </td>
                        <td class="td whitespace-nowrap">
                            @if ($p->employee->iban)
                                <span class="cursor-copy font-mono text-[11px] text-slate-600" title="Kopyala" @click="navigator.clipboard.writeText('{{ $p->employee->iban }}')">{{ $p->employee->formatted_iban }} <i class="fa-regular fa-copy text-slate-400"></i></span>
                                <div class="text-[11px] text-slate-400">{{ $p->employee->bank_name }}</div>
                            @else <span class="text-xs text-slate-400">—</span> @endif
                        </td>
                        <td class="td text-right whitespace-nowrap">@money($p->base_salary)</td>
                        <td class="td text-right text-emerald-700">{{ $p->bonus > 0 ? number_format($p->bonus, 2, ',', '.') : '—' }}</td>
                        <td class="td text-right text-red-600">{{ $p->advance > 0 ? number_format($p->advance, 2, ',', '.') : '—' }}</td>
                        <td class="td text-right text-red-600">{{ $p->deduction > 0 ? number_format($p->deduction, 2, ',', '.') : '—' }}</td>
                        <td class="td text-right font-semibold whitespace-nowrap">@money($p->net_amount)</td>
                        <td class="td text-right whitespace-nowrap {{ $p->status === 'paid' ? 'text-emerald-700' : '' }}">@money($p->paid_amount)
                            @if ($p->overpaid > 0)
                                <div class="mt-0.5">
                                    @if ($p->refund_pending > 0)
                                        <span class="badge-warn" title="Net tutardan fazla ödendi, personel iade edecek"><i class="fa-solid fa-rotate-left"></i>Fazla @money($p->overpaid) · iade bekleniyor</span>
                                    @else
                                        <span class="badge" title="İade alındı {{ $p->refund_at?->format('d.m.Y') }}"><i class="fa-solid fa-check"></i>Fazla @money($p->overpaid) · iade alındı</span>
                                    @endif
                                </div>
                            @endif</td>
                        <td class="td whitespace-nowrap">
                            @can('edit')
                            <form method="POST" action="{{ route('payments.status', $p) }}" class="block w-32">@csrf
                                <x-ui-select name="status" :search="false" :submit="true" class="status-select status-{{ $p->status }}">
                                    <option value="pending" @selected($p->status === 'pending')>Bekliyor</option>
                                    @if ($p->status === 'partial')<option value="partial" selected disabled>Kısmi</option>@endif
                                    <option value="paid" @selected($p->status === 'paid')>Ödendi</option>
                                </x-ui-select>
                            </form>
                            @else<x-status-badge :status="$p->status" />@endcan
                        </td>
                        <td class="td whitespace-nowrap text-xs text-slate-500">@if ($p->paid_at){{ $p->paid_at->format('d.m.Y') }} · {{ $p->method_label }}@else — @endif</td>
                        <td class="td text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-2">
                            @if ($p->refund_pending > 0 && auth()->user()->canEdit())
                                <x-confirm-form :action="route('payments.refund', $p)" method="POST" variant="success" title="İade alındı"
                                    :message="'Fazla ödenen '.number_format($p->refund_pending, 2, ',', '.').' ₺ personelden geri alındı olarak işaretlenecek.'"
                                    button="Evet, iade alındı" title="İade alındı olarak işaretle"><i class="fa-solid fa-rotate-left"></i></x-confirm-form>
                            @endif
                            @can('edit')<button type="button" @click="edit = true" class="btn-icon" title="Düzenle (prim, avans, kısmi ödeme)"><i class="fa-solid fa-pen"></i></button>@endcan
                            @can('delete')<x-confirm-form :action="route('payments.destroy', $p)" title="Kaydı sil" :message="$p->employee->full_name.' · '.$p->period_label.' maaş kaydı silinecek.'" title="Sil"><i class="fa-solid fa-trash"></i></x-confirm-form>@endcan
                            </div>

                            {{-- Düzenleme modalı --}}
                            <template x-teleport="body">
                                <template x-if="edit">
                                <div x-show="edit" @click.self="edit = false" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                                    <form method="POST" action="{{ route('payments.update', $p) }}"
                                          x-data="{ base: {{ (float) $p->base_salary }}, bonus: {{ (float) $p->bonus }}, advance: {{ (float) $p->advance }}, deduction: {{ (float) $p->deduction }}, paid: {{ (float) $p->paid_amount }},
                                                    get net() { return parseMoney(this.base) + parseMoney(this.bonus) - parseMoney(this.advance) - parseMoney(this.deduction); } }"
                                          class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl space-y-4 text-left">
                                        @csrf @method('PATCH')
                                        <div class="flex items-center justify-between">
                                            <div><h3 class="text-lg font-semibold">{{ $p->employee->full_name }}</h3><div class="text-xs text-slate-500">{{ $p->period_label }}</div></div>
                                            <button type="button" @click="edit = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button>
                                        </div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div><label class="form-label">Maaş</label><input name="base_salary" x-model="base" class="form-input text-right"></div>
                                            <div><label class="form-label">Prim (+)</label><input name="bonus" x-model="bonus" class="form-input text-right"></div>
                                            <div><label class="form-label">Avans (−)</label><input name="advance" x-model="advance" class="form-input text-right"></div>
                                            <div><label class="form-label">Kesinti (−)</label><input name="deduction" x-model="deduction" class="form-input text-right"></div>
                                        </div>
                                        <div class="flex items-center justify-between rounded-lg bg-slate-50 px-4 py-2 text-sm"><span class="text-slate-600">Net tutar</span><b x-text="formatMoney(net)"></b></div>
                                        <div class="grid grid-cols-3 gap-3">
                                            <div><label class="form-label">Ödenen</label><input name="paid_amount" x-model="paid" class="form-input text-right"></div>
                                            <div><label class="form-label">Yöntem</label><x-ui-select name="payment_method" :search="false" placeholder="—"><option value="">—</option>@foreach (\App\Models\SalaryPayment::METHODS as $k => $l)<option value="{{ $k }}" @selected($p->payment_method === $k)>{{ $l }}</option>@endforeach</x-ui-select></div>
                                            <div><label class="form-label">Tarih</label><x-date-input name="paid_at" :value="$p->paid_at" /></div>
                                        </div>
                                        <div class="flex gap-2 text-xs">
                                            <button type="button" @click="paid = net" class="rounded-full bg-emerald-50 px-3 py-1 text-emerald-700 hover:bg-emerald-100">Tamamı ödendi</button>
                                            <button type="button" @click="paid = Math.round(net / 2 * 100) / 100" class="rounded-full bg-amber-50 px-3 py-1 text-amber-700 hover:bg-amber-100">Yarısı</button>
                                            <button type="button" @click="paid = 0" class="rounded-full bg-slate-100 px-3 py-1 text-slate-700 hover:bg-slate-200">Sıfırla</button>
                                        </div>
                                        <div x-show="parseMoney(paid) > net + 0.004" x-cloak class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm">
                                            <div class="mb-2 flex items-center justify-between text-amber-800"><span><i class="fa-solid fa-rotate-left mr-1"></i>Fazla ödeme (para üstü)</span><b x-text="formatMoney(parseMoney(paid) - net)"></b></div>
                                            <div class="grid grid-cols-2 gap-3">
                                                <div><label class="form-label">İade alınan</label><input name="refund_amount" value="{{ $p->refund_amount > 0 ? number_format($p->refund_amount, 2, ',', '.') : '' }}" placeholder="0,00" class="form-input text-right"></div>
                                                <div><label class="form-label">İade tarihi</label><x-date-input name="refund_at" :value="$p->refund_at" /></div>
                                            </div>
                                            <p class="mt-2 text-[11px] text-amber-700">Personel farkı getirdiğinde "İade alınan" alanına yazın; listede "iade alındı" görünür.</p>
                                        </div>
                                        <div><label class="form-label">Açıklama</label><input name="note" value="{{ $p->note }}" class="form-input" placeholder="Örn. 2 gün devamsızlık kesintisi"></div>
                                        <div class="flex justify-end gap-2"><button type="button" @click="edit = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button></div>
                                    </form>
                                </div>
                                </template>
                            </template>
                        </td>
                    </tr>
                    @if ($p->note)<tr><td colspan="11" class="px-4 pb-2 -mt-2 text-[11px] text-slate-400"><i class="fa-regular fa-comment mr-1"></i>{{ $p->note }}</td></tr>@endif
                @empty
                    <tr><td colspan="11" class="px-5 py-12 text-center text-sm text-slate-500">
                        @if ($payrollTotals['count'] === 0) {{ $label }} için henüz bordro oluşturulmadı. @else Bu filtreye uyan kayıt yok. @endif
                    </td></tr>
                @endforelse
                </tbody>
                @if ($payments->isNotEmpty())
                <tfoot class="bg-slate-50 text-sm font-semibold"><tr>
                    <td class="px-4 py-3" colspan="2">Toplam ({{ $payments->count() }})</td>
                    <td class="px-4 py-3 text-right">@money($payments->sum('base_salary'))</td>
                    <td class="px-4 py-3 text-right text-emerald-700">@money($payments->sum('bonus'))</td>
                    <td class="px-4 py-3 text-right text-red-600">@money($payments->sum('advance'))</td>
                    <td class="px-4 py-3 text-right text-red-600">@money($payments->sum('deduction'))</td>
                    <td class="px-4 py-3 text-right">@money($payments->sum('net_amount'))</td>
                    <td class="px-4 py-3 text-right text-emerald-700">@money($payments->sum('paid_amount'))</td>
                    <td colspan="3"></td>
                </tr></tfoot>
                @endif
            </table>
        </div>
    </div>

    @if ($missing->isNotEmpty() && $payrollTotals['count'] > 0)
        <p class="text-xs text-slate-500"><i class="fa-solid fa-circle-info mr-1"></i>Bu dönemde kaydı olmayan aktif personel: {{ $missing->pluck('full_name')->join(', ') }}</p>
    @endif
