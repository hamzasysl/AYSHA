{{-- Notlar penceresi. show: parent x-data'daki değişken; title: ad, subtitle: tür/açıklama, meta: tarih, initials: avatar harfleri --}}
@props(['show', 'model', 'action', 'key' => null, 'title' => 'Notlar', 'subtitle' => null, 'meta' => null, 'initials' => null])
@php
    if ($initials === null) {
        $parts = preg_split('/\s+/', trim((string) $title));
        $initials = mb_strtoupper(mb_substr($parts[0] ?? '', 0, 1).mb_substr(count($parts) > 1 ? end($parts) : '', 0, 1), 'UTF-8');
    }
@endphp
<template x-teleport="body">
    <template x-if="{{ $show }}">
    <div x-show="{{ $show }}" @click.self="{{ $show }} = false" x-transition.opacity class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-[2px]">
        <div class="flex max-h-[85vh] w-full max-w-xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/5 text-left">
            <div class="flex items-center gap-3 border-b border-slate-100 px-5 py-4">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-full bg-brand-50 text-xs font-semibold tracking-wide text-brand-700 ring-1 ring-inset ring-brand-100">
                    @if ($initials !== '')<span>{{ $initials }}</span>@else<i class="fa-regular fa-note-sticky"></i>@endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="truncate text-[15px] font-medium leading-tight text-slate-900">{{ $title }}</div>
                    @if ($subtitle)<div class="mt-0.5 truncate text-xs text-slate-500">{{ $subtitle }}</div>@endif
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @if ($meta)<span class="rounded-md border border-slate-200 bg-slate-50 px-2 py-1 text-[11px] font-medium text-slate-500"><i class="fa-regular fa-calendar mr-1 text-slate-400"></i>{{ $meta }}</span>@endif
                    <button type="button" @click="{{ $show }} = false" class="grid h-8 w-8 place-items-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-700" title="Kapat"><i class="fa-solid fa-xmark"></i></button>
                </div>
            </div>
            <div class="overflow-y-auto bg-slate-50/60 p-4">
                <x-notes-panel :model="$model" :action="$action" :key="$key" :compact="true" />
            </div>
        </div>
    </div>
    </template>
</template>
