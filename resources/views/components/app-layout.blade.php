@props(['title' => null, 'subtitle' => null, 'section' => null])
@php
    $nav = [
        ['route' => 'dashboard', 'match' => 'dashboard', 'icon' => 'fa-chart-line', 'label' => 'Genel Bakış', 'desc' => 'Özet ve bekleyenler', 'tone' => 'text-brand-600 bg-brand-50'],
        ['route' => 'employees.index', 'match' => 'employees.*', 'icon' => 'fa-users', 'label' => 'Personel', 'desc' => 'Ekip ve kartlar', 'tone' => 'text-violet-600 bg-violet-50'],
        ['route' => 'expenses.index', 'match' => 'expenses.*|payments.*', 'icon' => 'fa-calculator', 'label' => 'Muhasebe', 'desc' => 'Maaş, yemek, yol, gider', 'tone' => 'text-emerald-600 bg-emerald-50'],
        ['route' => 'leaves.index', 'match' => 'leaves.*', 'icon' => 'fa-calendar-days', 'label' => 'İzinler', 'desc' => 'İzin, rapor, devamsızlık', 'tone' => 'text-amber-600 bg-amber-50'],
        ['route' => 'reviews.index', 'match' => 'reviews.*', 'icon' => 'fa-star', 'label' => 'Performans', 'desc' => 'Aylık değerlendirme', 'tone' => 'text-orange-500 bg-orange-50'],
        ['route' => 'reports.index', 'match' => 'reports.*', 'icon' => 'fa-chart-column', 'label' => 'Raporlar', 'desc' => 'Aylık ve yıllık özet', 'tone' => 'text-sky-600 bg-sky-50'],
        ['route' => 'settings.edit', 'match' => 'settings.*|users.*', 'icon' => 'fa-sliders', 'label' => 'Ayarlar', 'desc' => 'Hesap, ücretler, kullanıcılar', 'tone' => 'text-slate-600 bg-slate-100'],
    ];
    $current = collect($nav)->first(fn ($i) => request()->routeIs(...explode('|', $i['match'])));
    $section = $section ?? ($current['label'] ?? null);
@endphp
<!DOCTYPE html>
<html lang="tr">
<head>
    <script>try { if (localStorage.getItem('aysha_theme') === 'dark') document.documentElement.classList.add('dark'); } catch (e) {}</script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased text-slate-900">
<div x-data="{
        mobileOpen: false,
        collapsed: (() => { try { return localStorage.getItem('aysha_sidebar') === 'collapsed'; } catch (e) { return false; } })(),
        toggle() { this.collapsed = !this.collapsed; try { localStorage.setItem('aysha_sidebar', this.collapsed ? 'collapsed' : 'open'); } catch (e) {} }
     }" class="min-h-screen lg:flex">

    <div x-show="mobileOpen" x-cloak @click="mobileOpen = false" class="fixed inset-0 z-30 bg-slate-900/30 lg:hidden"></div>

    {{-- Sidebar --}}
    <aside :class="[mobileOpen ? 'translate-x-0' : '-translate-x-full', collapsed ? 'lg:w-[78px]' : 'lg:w-[240px]']"
           class="fixed inset-y-0 left-0 z-40 flex h-screen w-[240px] flex-col border-r border-slate-200/80 bg-white transition-all duration-200 lg:sticky lg:top-0 lg:translate-x-0 lg:shrink-0">

        {{-- Logo --}}
        <div class="flex items-center gap-3 px-4 pt-5 pb-4" :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
            <a href="{{ route('dashboard') }}" class="grid h-9 w-9 shrink-0 place-items-center rounded-lg text-white shadow-[0_8px_18px_-8px_rgba(30,111,242,.7)]" style="background: linear-gradient(135deg, #3573fb 0%, #1557d6 100%);" title="TTB Turizm">
                <i class="fa-solid fa-building text-sm"></i>
            </a>
            <a href="{{ route('dashboard') }}" class="min-w-0 leading-none" x-show="!collapsed">
                <span class="block truncate text-sm font-bold tracking-tight text-slate-900">TTB Turizm</span>
                <span class="mt-0.5 block truncate text-[11px] font-medium text-slate-500">Personel Yönetimi</span>
            </a>
            <button @click="mobileOpen = false" class="ml-auto text-slate-500 lg:hidden"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <nav class="flex-1 space-y-1.5 overflow-y-auto px-3 py-2">
            @foreach ($nav as $item)
                @php $active = request()->routeIs(...explode('|', $item['match'])); @endphp
                <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}"
                   :class="collapsed ? 'lg:justify-center lg:px-0' : ''"
                   class="group flex items-center gap-2.5 rounded-xl border px-2.5 py-2 transition
                          {{ $active ? 'border-brand-600 bg-brand-600 text-white shadow-[0_8px_20px_-8px_rgba(30,111,242,.6)]' : 'border-slate-200/80 bg-white text-slate-800 hover:border-brand-200 hover:bg-brand-50/40' }}">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-[13px] {{ $active ? 'bg-white/15 text-white' : $item['tone'] }}"><i class="fa-solid {{ $item['icon'] }}"></i></span>
                    <span class="min-w-0 leading-tight" x-show="!collapsed">
                        <span class="block truncate text-[13px] font-bold">{{ $item['label'] }}</span>
                        <span class="block truncate text-[11px] {{ $active ? 'text-white/80' : 'text-slate-500' }}">{{ $item['desc'] }}</span>
                    </span>
                </a>
            @endforeach
        </nav>

        {{-- Alt: karanlık mod + kullanıcı --}}
        <div class="space-y-1.5 border-t border-slate-200/80 p-3" x-data="{ dark: document.documentElement.classList.contains('dark') }">
            <div class="flex items-center gap-1.5" :class="collapsed ? 'lg:flex-col' : ''">
                <button type="button" @click="toggleTheme(); dark = isDark()" :class="collapsed ? 'lg:justify-center lg:px-0' : ''" class="flex min-w-0 flex-1 items-center gap-2.5 rounded-xl px-2 py-1.5 text-left transition hover:bg-slate-100" :title="dark ? 'Aydınlık moda geç' : 'Karanlık moda geç'">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-indigo-50 text-[13px] text-indigo-600"><i class="fa-solid" :class="dark ? 'fa-sun' : 'fa-moon'"></i></span>
                    <span class="truncate text-[13px] font-semibold text-slate-700" x-show="!collapsed" x-text="dark ? 'Aydınlık mod' : 'Karanlık mod'"></span>
                </button>
                <button @click="toggle()" class="hidden h-8 w-8 shrink-0 place-items-center rounded-lg text-slate-400 hover:bg-slate-100 hover:text-slate-700 lg:grid" :title="collapsed ? 'Menüyü genişlet' : 'Menüyü daralt'"><i class="fa-solid" :class="collapsed ? 'fa-angles-right' : 'fa-angles-left'"></i></button>
            </div>
            <div class="flex items-center gap-2.5 rounded-xl px-2 py-1.5" :class="collapsed ? 'lg:justify-center lg:px-0' : ''">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-violet-50 text-[13px] text-violet-600" title="{{ auth()->user()->name }}"><i class="fa-solid fa-user"></i></span>
                <span class="min-w-0 flex-1 leading-tight" x-show="!collapsed">
                    <span class="block truncate text-[13px] font-semibold text-slate-800">{{ auth()->user()->name }}</span>
                    <span class="block truncate text-[11px] text-slate-500">{{ auth()->user()->roleLabel() }}</span>
                </span>
                <form method="POST" action="{{ route('logout') }}" x-show="!collapsed">
                    @csrf
                    <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600" title="Çıkış yap"><i class="fa-solid fa-right-from-bracket"></i></button>
                </form>
            </div>
            <form method="POST" action="{{ route('logout') }}" x-show="collapsed" x-cloak class="hidden lg:flex lg:justify-center">
                @csrf
                <button type="submit" class="grid h-8 w-8 place-items-center rounded-lg text-slate-400 hover:bg-red-50 hover:text-red-600" title="Çıkış yap"><i class="fa-solid fa-right-from-bracket"></i></button>
            </form>
        </div>
    </aside>

    {{-- İçerik --}}
    <div class="flex min-w-0 flex-1 flex-col">
        {{-- Mobil üst bar --}}
        <header class="sticky top-0 z-20 flex h-14 items-center gap-3 border-b border-slate-200 bg-white px-4 lg:hidden">
            <button @click="mobileOpen = true" class="grid h-10 w-10 place-items-center rounded-xl border border-slate-200 text-slate-700"><i class="fa-solid fa-bars"></i></button>
            <span class="truncate text-sm font-bold">{{ $title }}</span>
        </header>

        <main class="flex-1 space-y-5 p-4 sm:p-5 lg:p-6">
            {{-- Sayfa başlığı kartı --}}
            <div class="card flex flex-col gap-3 px-5 py-3.5 md:flex-row md:items-center md:justify-between">
                <div class="min-w-0">
                    @if ($section)<div class="text-[11px] font-semibold text-brand-600">{{ $section }}</div>@endif
                    <h1 class="mt-0.5 truncate text-lg font-bold tracking-tight text-slate-900">{{ $title }}</h1>
                    @if ($subtitle)<p class="mt-0.5 text-xs text-slate-500">{{ $subtitle }}</p>@endif
                </div>
                @isset($actions)
                    <div class="page-actions flex flex-wrap items-center gap-2 md:justify-end">{{ $actions }}</div>
                @endisset
            </div>

            <x-flash />
            {{ $slot }}
        </main>
    </div>
</div>
</body>
</html>
