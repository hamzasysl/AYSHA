{{-- Sistem bakım kartları: Ayarlar > Sistem sekmesi ve /ayarlar/sistem sayfası ortak kullanır. --}}
@php $sys = \App\Http\Controllers\SystemController::status(); @endphp
            <div class="card overflow-hidden">
                <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><i class="fa-solid fa-rotate"></i></div>
                    <div class="flex-1">
                        <h2 class="text-base font-semibold text-slate-900">Güncelleme sonrası</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Yazılım güncellendikten sonra (cPanel &gt; Git &gt; Deploy) bu düğmeye basın. Veritabanına eklenen yeni alanlar uygulanır ve önbellek temizlenir. Sunucuda komut satırına gerek yoktur.</p>
                    </div>
                </div>
                <div class="px-6 py-5">
                    @if ($sys['error'])
                        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">Veritabanı durumu okunamadı: {{ $sys['error'] }}</div>
                    @elseif (count($sys['pending']) > 0)
                        <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            <b>{{ count($sys['pending']) }} bekleyen veritabanı güncellemesi var.</b> Aşağıdaki düğmeye basın.
                            <div class="mt-1 text-[11px] text-amber-700">{{ implode(', ', array_map(fn ($p) => basename($p), $sys['pending'])) }}</div>
                        </div>
                    @else
                        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><i class="fa-solid fa-circle-check mr-1"></i> Veritabanı güncel, bekleyen güncelleme yok.</div>
                    @endif

                    <div class="flex flex-wrap gap-2">
                        <form method="POST" action="{{ route('system.migrate') }}">@csrf
                            <button class="btn-success"><i class="fa-solid fa-rotate"></i> Güncellemeleri uygula</button>
                        </form>
                        <form method="POST" action="{{ route('system.cache') }}">@csrf
                            <button class="btn-secondary"><i class="fa-solid fa-broom"></i> Önbelleği temizle</button>
                        </form>
                    </div>
                    <p class="mt-3 text-xs text-slate-500">Yeni bir görünüm veya tasarım değişikliği sonrası sayfa eski görünüyorsa "Önbelleği temizle" yeterlidir.</p>
                </div>
            </div>

            <div class="card px-6 py-5">
                <h3 class="mb-3 text-sm font-semibold text-slate-900"><i class="fa-solid fa-circle-info text-slate-400"></i>Sunucu bilgileri</h3>
                <div class="grid gap-x-8 gap-y-2 text-sm sm:grid-cols-2">
                    <div class="flex justify-between border-b border-slate-50 py-1"><span class="text-slate-500">PHP sürümü</span><span class="font-medium text-slate-800">{{ $sys['php'] }}</span></div>
                    <div class="flex justify-between border-b border-slate-50 py-1"><span class="text-slate-500">Laravel</span><span class="font-medium text-slate-800">{{ $sys['laravel'] }}</span></div>
                    <div class="flex justify-between border-b border-slate-50 py-1"><span class="text-slate-500">Veritabanı</span><span class="font-medium text-slate-800">{{ $sys['db'] }}</span></div>
                    <div class="flex justify-between border-b border-slate-50 py-1"><span class="text-slate-500">Ortam</span><span class="font-medium text-slate-800">{{ $sys['env'] }}@if ($sys['debug'])<span class="badge-danger ml-2">hata ayıklama açık</span>@endif</span></div>
                </div>
            </div>
