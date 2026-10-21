{{-- Sistem bakım kartları: Ayarlar > Sistem sekmesi ve /ayarlar/sistem sayfası ortak kullanır.
     Ekrana teknik ayrıntı (sunucu, dosya adı, altyapı) YAZMA — kullanıcıya sade görünmeli. --}}
@php $sys = \App\Http\Controllers\SystemController::status(); @endphp
            <div class="card overflow-hidden">
                <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><i class="fa-solid fa-rotate"></i></div>
                    <div class="flex-1">
                        <h2 class="text-base font-semibold text-slate-900">Bakım</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Yeni bir güncelleme geldiğinde uygulayın; sayfalar eski görünüyorsa yenileyin.</p>
                    </div>
                </div>
                <div class="px-6 py-5">
                    @if ($sys['error'])
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Durum okunamadı.</div>
                    @elseif (count($sys['pending']) > 0)
                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <b>{{ count($sys['pending']) }} güncelleme bekliyor.</b> Aşağıdaki düğmeye basın.
                        </div>
                    @else
                        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><i class="fa-solid fa-circle-check mr-1"></i> Her şey güncel.</div>
                    @endif

                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('system.migrate') }}">@csrf
                            <button class="btn-success"><i class="fa-solid fa-rotate"></i> Güncellemeleri uygula</button>
                        </form>
                        <form method="POST" action="{{ route('system.cache') }}">@csrf
                            <button class="btn-secondary"><i class="fa-solid fa-broom"></i> Sayfaları yenile</button>
                        </form>
                    </div>
                </div>
            </div>
