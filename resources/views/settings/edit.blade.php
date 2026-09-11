@php $fmt = fn ($x) => number_format((float) $x, 2, ',', '.'); $manager = auth()->user()->isManager(); $tab = $manager ? request('tab', old('_tab', 'allowances')) : 'account'; @endphp
<x-app-layout title="Ayarlar" subtitle="Ödenekler, şirket ve banka bilgileri, hesabınız">
    <div x-data="{ tab: '{{ $tab }}' }" class="grid gap-6 lg:grid-cols-[240px_1fr]">
        {{-- Sol menü --}}
        <aside class="lg:sticky lg:top-24 lg:self-start">
            <nav class="card p-2">
                @php $tabs = ['allowances' => ['fa-utensils', 'Yemek & Yol Ücretleri', 'Aylık standart tutarlar'], 'company' => ['fa-building-columns', 'Şirket & Banka', 'Garanti maaş dosyası bilgileri'], 'account' => ['fa-user-shield', 'Hesap', 'Ad, kullanıcı adı, şifre'], 'users' => ['fa-users-gear', 'Kullanıcılar', 'Sisteme giriş yapabilenler'], 'categories' => ['fa-tags', 'Gider Kategorileri', 'Muhasebedeki kategoriler'], 'lists' => ['fa-list-check', 'Listeler', 'Görevler, bankalar, izin türleri']]; if (! $manager) { $tabs = ['account' => $tabs['account']]; } @endphp
                @foreach ($tabs as $key => [$icon, $label, $desc])
                    <button type="button" @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-brand-50 text-brand-700' : 'text-slate-600 hover:bg-slate-50'" class="flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-left transition">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg border border-slate-200 bg-white"><i class="fa-solid {{ $icon }} text-sm" :class="tab === '{{ $key }}' ? 'text-brand-600' : 'text-slate-400'"></i></span>
                        <span class="min-w-0"><span class="block text-sm font-medium">{{ $label }}</span><span class="block truncate text-[11px] text-slate-400">{{ $desc }}</span></span>
                    </button>
                @endforeach
            </nav>
        </aside>

        <div class="space-y-6">
            @if ($manager)
            {{-- Ödenekler --}}
            <form x-show="tab === 'allowances'" x-cloak method="POST" action="{{ route('settings.update') }}" class="card overflow-hidden">
                @csrf @method('PUT') <input type="hidden" name="_tab" value="allowances">
                <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><i class="fa-solid fa-utensils"></i></div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Yemek &amp; Yol Ücretleri</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Personel kartında özel tutar girilmemişse bu değerler kullanılır. Muhasebe'deki "Yemek &amp; Yol Oluştur" bu tutarlarla kayıt açar.</p>
                    </div>
                </div>
                <div class="px-6 py-5">
                    <div class="overflow-hidden rounded-xl border border-slate-200">
                        <table class="min-w-full">
                            <thead><tr class="bg-slate-50"><th class="th !bg-transparent w-1/3">Ödenek</th><th class="th !bg-transparent"><i class="fa-solid fa-user-check mr-1.5 text-brand-500"></i>Normal personel</th><th class="th !bg-transparent"><i class="fa-solid fa-user-clock mr-1.5 text-amber-500"></i>Emekli personel</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach ([['Yemek ücreti', 'meal_allowance', 'fa-utensils'], ['Yol ücreti', 'travel_allowance', 'fa-bus']] as [$label, $key, $icon])
                                    <tr>
                                        <td class="td"><span class="inline-flex items-center gap-2 font-medium text-slate-800"><i class="fa-solid {{ $icon }} text-slate-400"></i>{{ $label }}</span><div class="text-[11px] text-slate-400">aylık, kişi başı</div></td>
                                        @foreach (['', '_retired'] as $suffix)
                                            <td class="td">
                                                <div class="relative">
                                                    <input name="{{ $key.$suffix }}" value="{{ $fmt(old($key.$suffix, $values[$key.$suffix])) }}" required inputmode="decimal" class="form-input pr-8 text-right font-medium tabular-nums">
                                                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">₺</span>
                                                </div>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="mt-3 text-xs text-slate-500"><i class="fa-solid fa-circle-info mr-1 text-slate-400"></i>Emekli olarak işaretlenen personel için emekli sütunu geçerlidir. Değişiklik, özel tutarı olmayan tüm personele anında yansır.</p>
                </div>
                <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                    <span class="text-xs text-slate-400">Son güncelleme kaydedildiğinde uygulanır.</span>
                    <button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
                </div>
            </form>

            {{-- Şirket & Banka --}}
            <form x-show="tab === 'company'" x-cloak method="POST" action="{{ route('settings.update') }}" class="card overflow-hidden">
                @csrf @method('PUT') <input type="hidden" name="_tab" value="company">
                @foreach (['meal_allowance', 'meal_allowance_retired', 'travel_allowance', 'travel_allowance_retired'] as $k)<input type="hidden" name="{{ $k }}" value="{{ $fmt($values[$k]) }}">@endforeach
                <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><i class="fa-solid fa-building-columns"></i></div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Şirket &amp; Banka</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Muhasebe &gt; Maaşlar'dan indirilen Garanti "TGB Yeni Maaş Dosyası" bu bilgilerle doldurulur.</p>
                    </div>
                </div>
                <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                    <div class="sm:col-span-2"><label class="form-label">Şirket adı</label><input name="company_name" value="{{ old('company_name', $values['company_name']) }}" class="form-input"></div>
                    <div><label class="form-label">Banka kurum kodu</label><div class="relative"><i class="fa-solid fa-hashtag pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i><input name="bank_corp_code" value="{{ old('bank_corp_code', $values['bank_corp_code']) }}" placeholder="615399" class="form-input pl-8 font-mono"></div></div>
                    <div><label class="form-label">Şube kodu</label><div class="relative"><i class="fa-solid fa-code-branch pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i><input name="bank_branch_code" value="{{ old('bank_branch_code', $values['bank_branch_code']) }}" placeholder="544" class="form-input pl-8 font-mono"></div></div>
                    <div class="sm:col-span-2"><label class="form-label">Maaş ödeme hesabı</label><div class="relative"><i class="fa-solid fa-wallet pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i><input name="bank_account" value="{{ old('bank_account', $values['bank_account']) }}" placeholder="6290915" class="form-input pl-8 font-mono"></div></div>
                    <div class="sm:col-span-2 rounded-xl border border-dashed border-slate-200 bg-slate-50/60 px-4 py-3 text-xs text-slate-500">
                        <i class="fa-solid fa-file-excel mr-1 text-emerald-600"></i>Banka dosyasında personelin IBAN, şube ve hesap bilgileri personel kartından, tutar bordrodan gelir. Bu alanlar dosyanın üst bloğunu (kurum / şube / hesap) doldurur.
                    </div>
                </div>
                <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                    <button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
                </div>
            </form>

            @endif

            {{-- Hesap --}}
            <form x-show="tab === 'account'" x-cloak method="POST" action="{{ route('settings.account') }}" class="card overflow-hidden">
                @csrf @method('PUT') <input type="hidden" name="_tab" value="account">
                <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><i class="fa-solid fa-user-shield"></i></div>
                    <div>
                        <h2 class="text-base font-semibold text-slate-900">Hesap</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Giriş bilgileriniz. Şifre alanlarını boş bırakırsanız şifre değişmez.</p>
                    </div>
                </div>
                <div class="grid gap-5 px-6 py-5 sm:grid-cols-2">
                    <div><label class="form-label">Ad Soyad</label><input name="name" value="{{ old('name', auth()->user()->name) }}" required class="form-input"></div>
                    <div><label class="form-label">Kullanıcı adı</label><div class="relative"><i class="fa-solid fa-at pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400"></i><input name="username" value="{{ old('username', auth()->user()->username) }}" required class="form-input pl-8"></div></div>
                    <div class="sm:col-span-2"><label class="form-label">E-posta</label><input type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required class="form-input"></div>
                    <div class="sm:col-span-2 border-t border-slate-100 pt-4"><div class="text-xs font-semibold uppercase tracking-wider text-slate-400">Şifre değiştir</div></div>
                    <div x-data="{ show: false }"><label class="form-label">Mevcut şifre</label><div class="relative"><input :type="show ? 'text' : 'password'" name="current_password" autocomplete="current-password" class="form-input pr-10"><button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 grid h-7 w-7 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Göster / gizle"><i class="fa-regular" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i></button></div></div>
                    <div></div>
                    <div x-data="{ show: false }"><label class="form-label">Yeni şifre</label><div class="relative"><input :type="show ? 'text' : 'password'" name="password" autocomplete="new-password" class="form-input pr-10"><button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 grid h-7 w-7 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Göster / gizle"><i class="fa-regular" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i></button></div></div>
                    <div x-data="{ show: false }"><label class="form-label">Yeni şifre (tekrar)</label><div class="relative"><input :type="show ? 'text' : 'password'" name="password_confirmation" autocomplete="new-password" class="form-input pr-10"><button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 grid h-7 w-7 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700" title="Göster / gizle"><i class="fa-regular" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i></button></div></div>
                </div>
                <div class="flex items-center justify-end border-t border-slate-100 bg-slate-50/60 px-6 py-4">
                    <button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
                </div>
            </form>

            @if ($manager)
            {{-- Kullanıcılar --}}
            <div x-show="tab === 'users'" x-cloak class="space-y-6">
                @if (session('shown_password'))
                    @php $sp = session('shown_password'); @endphp
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4 text-sm text-emerald-900" x-data="{ show: true }">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="font-semibold"><i class="fa-solid fa-key mr-1"></i>{{ $sp['name'] }} için giriş bilgileri</div>
                                <div class="mt-1">Kullanıcı adı: <code class="rounded bg-white px-1.5 py-0.5 font-mono">{{ $sp['username'] }}</code> · Şifre: <code class="rounded bg-white px-1.5 py-0.5 font-mono" x-text="show ? @js($sp['password']) : '••••••••'"></code>
                                    <button type="button" @click="show = !show" class="ml-1 text-emerald-700 hover:underline" x-text="show ? 'gizle' : 'göster'"></button></div>
                                <div class="mt-1 text-xs text-emerald-700">Şifre bu ekrandan sonra bir daha görüntülenemez; not alın. Gerekirse kullanıcıya yeni şifre verebilirsiniz.</div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card overflow-hidden">
                    <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><i class="fa-solid fa-users-gear"></i></div>
                        <div class="flex-1">
                            <h2 class="text-base font-semibold text-slate-900">Kullanıcılar</h2>
                            <p class="mt-0.5 text-sm text-slate-500">Sisteme giriş yapabilen hesaplar. Şifreler şifrelenmiş saklanır; mevcut şifre görüntülenemez, ama istediğiniz an yeni şifre verebilirsiniz.</p>
                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-[11px] text-slate-500">@foreach (\App\Models\User::ROLES as $rk => [$rl, $rd])<span><b class="text-slate-700">{{ $rl }}:</b> {{ $rd }}</span>@endforeach</div>
                        </div>
                        <div x-data="{ open: {{ $errors->any() && old('_form') === 'user-create' ? 'true' : 'false' }} }">
                            <button type="button" @click="open = true" class="btn-primary"><i class="fa-solid fa-user-plus"></i> Yeni Kullanıcı</button>
                            <template x-teleport="body">
                                <div x-show="open" @click.self="open = false" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                    <form method="POST" action="{{ route('users.store') }}" class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl" x-data="{ show: true }">
                                        @csrf <input type="hidden" name="_form" value="user-create"><input type="hidden" name="_tab" value="users">
                                        <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Yeni kullanıcı</h3><button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                                        <div><label class="form-label">Ad Soyad *</label><input name="name" value="{{ old('name') }}" required class="form-input"></div>
                                        <div class="grid grid-cols-2 gap-3">
                                            <div><label class="form-label">Kullanıcı adı *</label><input name="username" value="{{ old('username') }}" required class="form-input"></div>
                                            <div><label class="form-label">Yetki *</label><x-ui-select name="role" :search="false">@foreach (\App\Http\Controllers\UserController::ROLES as $k => $l)<option value="{{ $k }}" @selected(old('role', 'admin') === $k)>{{ $l }}</option>@endforeach</x-ui-select></div>
                                        </div>
                                        <div><label class="form-label">E-posta *</label><input type="email" name="email" value="{{ old('email') }}" required class="form-input"></div>
                                        <div><label class="form-label">Şifre *</label><div class="relative"><input :type="show ? 'text' : 'password'" name="password" value="{{ old('password') }}" required minlength="6" autocomplete="new-password" class="form-input pr-10"><button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 grid h-7 w-7 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700"><i class="fa-regular" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i></button></div><p class="mt-1 text-[11px] text-slate-500">En az 6 karakter. Kaydettikten sonra bir kez daha gösterilir.</p></div>
                                        <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Oluştur</button></div>
                                    </form>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-hover">
                            <thead><tr><th class="th">Kullanıcı</th><th class="th">Kullanıcı adı</th><th class="th">E-posta</th><th class="th">Yetki</th><th class="th">Oluşturma</th><th class="th text-right">İşlem</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                            @foreach ($users as $u)
                                <tr x-data="{ edit: false }">
                                    <td class="td whitespace-nowrap">
                                        <div class="flex items-center gap-3">
                                            <span class="grid h-9 w-9 place-items-center rounded-full bg-brand-50 text-xs font-semibold text-brand-700">{{ mb_strtoupper(mb_substr($u->name, 0, 1)) }}</span>
                                            <span><span class="block font-medium text-slate-900">{{ $u->name }}</span>@if ($u->id === auth()->id())<span class="text-[11px] text-slate-400">siz</span>@endif</span>
                                        </div>
                                    </td>
                                    <td class="td font-mono text-xs">{{ $u->username }}</td>
                                    <td class="td">{{ $u->email }}</td>
                                    <td class="td"><span class="{{ in_array($u->role, ['admin', 'owner']) ? 'badge-info' : ($u->role === 'editor' ? 'badge-ok' : 'badge') }}" title="{{ \App\Models\User::ROLES[$u->role][1] ?? '' }}">{{ $u->roleLabel() }}</span></td>
                                    <td class="td text-xs text-slate-500">{{ $u->created_at->format('d.m.Y') }}</td>
                                    <td class="td text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" @click="edit = true" class="btn-icon" title="Düzenle / şifre ver"><i class="fa-solid fa-pen"></i></button>
                                            @if ($u->id !== auth()->id())
                                                <x-confirm-form :action="route('users.destroy', $u)" title="Kullanıcıyı sil" :message="$u->name.' artık giriş yapamayacak.'" title="Sil"><i class="fa-solid fa-trash"></i></x-confirm-form>
                                            @endif
                                        </div>
                                        <template x-teleport="body">
                                            <template x-if="edit">
                                            <div x-show="edit" @click.self="edit = false" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                                <form method="POST" action="{{ route('users.update', $u) }}" class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl text-left" x-data="{ show: true }">
                                                    @csrf @method('PUT')
                                                    <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">{{ $u->name }}</h3><button type="button" @click="edit = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                                                    <div><label class="form-label">Ad Soyad *</label><input name="name" value="{{ $u->name }}" required class="form-input"></div>
                                                    <div class="grid grid-cols-2 gap-3">
                                                        <div><label class="form-label">Kullanıcı adı *</label><input name="username" value="{{ $u->username }}" required class="form-input"></div>
                                                        <div><label class="form-label">Yetki *</label><x-ui-select name="role" :search="false">@foreach (\App\Http\Controllers\UserController::ROLES as $k => $l)<option value="{{ $k }}" @selected($u->role === $k)>{{ $l }}</option>@endforeach</x-ui-select></div>
                                                    </div>
                                                    <div><label class="form-label">E-posta *</label><input type="email" name="email" value="{{ $u->email }}" required class="form-input"></div>
                                                    <div><label class="form-label">Yeni şifre <span class="font-normal text-slate-400">(boşsa değişmez)</span></label><div class="relative"><input :type="show ? 'text' : 'password'" name="password" minlength="6" autocomplete="new-password" class="form-input pr-10"><button type="button" @click="show = !show" class="absolute right-2 top-1/2 -translate-y-1/2 grid h-7 w-7 place-items-center rounded-md text-slate-400 hover:bg-slate-100 hover:text-slate-700"><i class="fa-regular" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i></button></div></div>
                                                    <div class="flex justify-end gap-2"><button type="button" @click="edit = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button></div>
                                                </form>
                                            </div>
                                            </template>
                                        </template>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Gider kategorileri --}}
            <div x-show="tab === 'categories'" x-cloak class="card overflow-hidden">
                <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                    <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><i class="fa-solid fa-tags"></i></div>
                    <div class="flex-1">
                        <h2 class="text-base font-semibold text-slate-900">Gider Kategorileri</h2>
                        <p class="mt-0.5 text-sm text-slate-500">Muhasebe'de kayıt açarken seçilen kategoriler. "Personele bağlı" olanlar raporlarda kişi bazında <b>Ekstra</b> altında toplanır; genel giderler (temizlik malzemesi gibi) ayrı sayılır.</p>
                    </div>
                    <div x-data="{ open: {{ $errors->any() && old('_form') === 'category-create' ? 'true' : 'false' }} }">
                        <button type="button" @click="open = true" class="btn-primary"><i class="fa-solid fa-plus"></i> Yeni Kategori</button>
                        <template x-teleport="body">
                            <div x-show="open" @click.self="open = false" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                <form method="POST" action="{{ route('categories.store') }}" class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl">
                                    @csrf <input type="hidden" name="_form" value="category-create"><input type="hidden" name="_tab" value="categories">
                                    <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Yeni kategori</h3><button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                                    @include('settings._category_fields', ['cat' => null])
                                    <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Ekle</button></div>
                                </form>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-hover">
                        <thead><tr><th class="th">Kategori</th><th class="th">Tür</th><th class="th text-center">Kayıt</th><th class="th text-right">İşlem</th></tr></thead>
                        <tbody class="divide-y divide-slate-100">
                        @foreach ($categories as $c)
                            <tr x-data="{ edit: false }">
                                <td class="td whitespace-nowrap">
                                    <span class="inline-flex items-center gap-2.5">
                                        <span class="grid h-8 w-8 place-items-center rounded-lg bg-{{ $c->color }}-50 text-{{ $c->color }}-600"><i class="fa-solid {{ $c->icon }} text-xs"></i></span>
                                        <span class="font-semibold text-slate-900">{{ $c->label }}</span>
                                        @if ($c->is_system)<span class="badge" title="Sistem kategorisi, silinemez">sistem</span>@endif
                                    </span>
                                </td>
                                <td class="td">{{ $c->employee_based ? 'Personele bağlı ödeme' : 'Genel gider' }}</td>
                                <td class="td text-center text-slate-500">{{ $categoryUsage[$c->slug] ?? 0 }}</td>
                                <td class="td text-right whitespace-nowrap">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" @click="edit = true" class="btn-icon" title="Düzenle"><i class="fa-solid fa-pen"></i></button>
                                        @if (! $c->is_system)
                                            <x-confirm-form :action="route('categories.destroy', $c)" title="Kategoriyi sil" :message="$c->label.' silinecek. Kayıt varsa silinmez.'" title="Sil"><i class="fa-solid fa-trash"></i></x-confirm-form>
                                        @endif
                                    </div>
                                    <template x-teleport="body">
                                        <template x-if="edit">
                                        <div x-show="edit" @click.self="edit = false" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                            <form method="POST" action="{{ route('categories.update', $c) }}" class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl text-left">
                                                @csrf @method('PUT')
                                                <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">{{ $c->label }}</h3><button type="button" @click="edit = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                                                @include('settings._category_fields', ['cat' => $c])
                                                <div class="flex justify-end gap-2"><button type="button" @click="edit = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button></div>
                                            </form>
                                        </div>
                                        </template>
                                    </template>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Listeler: görevler, bankalar, izin türleri --}}
            <div x-show="tab === 'lists'" x-cloak x-data="{ list: '{{ request('list', 'position') }}' }" class="space-y-4">
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Models\ListItem::TYPES as $type => $meta)
                        <button type="button" @click="list = '{{ $type }}'" :class="list === '{{ $type }}' ? 'chip chip-active' : 'chip'"><i class="fa-solid {{ $meta['icon'] }}"></i> {{ $meta['label'] }} <span class="opacity-70">{{ $lists[$type]->count() }}</span></button>
                    @endforeach
                </div>
                @foreach (\App\Models\ListItem::TYPES as $type => $meta)
                <div x-show="list === '{{ $type }}'" class="card overflow-hidden">
                    <div class="flex items-start gap-4 border-b border-slate-100 px-6 py-5">
                        <div class="grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-600"><i class="fa-solid {{ $meta['icon'] }}"></i></div>
                        <div class="flex-1">
                            <h2 class="text-base font-semibold text-slate-900">{{ $meta['label'] }}</h2>
                            <p class="mt-0.5 text-sm text-slate-500">{{ $meta['desc'] }}.@if ($type !== 'leave_type') Ad değişince personel kartlarındaki değer de güncellenir.@endif</p>
                        </div>
                        <div x-data="{ open: {{ $errors->any() && old('_form') === 'list-'.$type ? 'true' : 'false' }} }">
                            <button type="button" @click="open = true" class="btn-primary"><i class="fa-solid fa-plus"></i> Yeni</button>
                            <template x-teleport="body">
                                <div x-show="open" @click.self="open = false" x-cloak x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                    <form method="POST" action="{{ route('lists.store', $type) }}" class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl">
                                        @csrf <input type="hidden" name="_form" value="list-{{ $type }}"><input type="hidden" name="_tab" value="lists">
                                        <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">Yeni · {{ $meta['label'] }}</h3><button type="button" @click="open = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                                        @include('settings._list_fields', ['type' => $type, 'item' => null])
                                        <div class="flex justify-end gap-2"><button type="button" @click="open = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Ekle</button></div>
                                    </form>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full table-hover">
                            <thead><tr><th class="th">Ad</th>@if ($type === 'leave_type')<th class="th">Yıllık izinden</th>@endif<th class="th text-center">Kullanım</th><th class="th text-right">İşlem</th></tr></thead>
                            <tbody class="divide-y divide-slate-100">
                            @foreach ($lists[$type] as $item)
                                <tr x-data="{ edit: false }">
                                    <td class="td whitespace-nowrap">
                                        <span class="inline-flex items-center gap-2.5">
                                            @if ($type === 'leave_type')<span class="grid h-8 w-8 place-items-center rounded-lg bg-slate-100 text-slate-600"><i class="fa-solid {{ $item->meta['icon'] ?? 'fa-calendar' }} text-xs"></i></span>@endif
                                            <span class="font-semibold text-slate-900">{{ $item->label }}</span>
                                            @if ($item->is_system)<span class="badge" title="Sistem öğesi, silinemez">sistem</span>@endif
                                        </span>
                                    </td>
                                    @if ($type === 'leave_type')<td class="td">@if ($item->meta['deduct'] ?? false)<span class="badge-ok">Düşer</span>@else<span class="badge">Düşmez</span>@endif</td>@endif
                                    <td class="td text-center text-slate-500">{{ $listUsage[$type][$type === 'leave_type' ? $item->slug : $item->label] ?? 0 }}</td>
                                    <td class="td text-right whitespace-nowrap">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" @click="edit = true" class="btn-icon" title="Düzenle"><i class="fa-solid fa-pen"></i></button>
                                            @if (! $item->is_system)<x-confirm-form :action="route('lists.destroy', $item)" title="Sil" :message="$item->label.' silinecek. Kullanımda ise silinmez.'" title="Sil"><i class="fa-solid fa-trash"></i></x-confirm-form>@endif
                                        </div>
                                        <template x-teleport="body">
                                            <template x-if="edit">
                                            <div x-show="edit" @click.self="edit = false" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
                                                <form method="POST" action="{{ route('lists.update', $item) }}" class="w-full max-w-md space-y-4 rounded-xl bg-white p-6 shadow-xl text-left">
                                                    @csrf @method('PUT')
                                                    <div class="flex items-center justify-between"><h3 class="text-lg font-semibold">{{ $item->label }}</h3><button type="button" @click="edit = false" class="text-slate-400 hover:text-slate-700"><i class="fa-solid fa-xmark text-lg"></i></button></div>
                                                    @include('settings._list_fields', ['type' => $type, 'item' => $item])
                                                    <div class="flex justify-end gap-2"><button type="button" @click="edit = false" class="btn-secondary">Vazgeç</button><button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button></div>
                                                </form>
                                            </div>
                                            </template>
                                        </template>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
