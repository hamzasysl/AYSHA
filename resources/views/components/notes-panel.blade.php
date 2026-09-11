{{-- Not listesi (AJAX): ekle, düzenle, sil — sayfa yenilenmez. $model: HasNotes modeli, $action: POST rotası, $key: sayaç anahtarı --}}
@props(['model', 'action', 'redirect' => null, 'compact' => false, 'key' => null])
@php
    $initial = $model->notes->map(fn ($n) => [
        'id' => $n->id, 'content' => $n->content, 'author' => $n->author?->name ?? '—',
        'at' => $n->created_at->format('d.m.Y H:i'), 'human' => $n->created_at->diffForHumans(),
        'edited' => $n->updated_at->gt($n->created_at->copy()->addMinute()) ? $n->updated_at->format('d.m.Y H:i') : null,
    ])->values();
@endphp
@php $canEdit = auth()->user()->canEdit(); $canDelete = auth()->user()->canDelete(); @endphp
<div x-data="notesPanel({ notes: @js($initial), storeUrl: @js($action), key: @js($key) })" {{ $attributes->merge(['class' => 'flex flex-col']) }}>
    <div class="space-y-2 {{ $compact ? 'max-h-[55vh] overflow-y-auto pr-1' : '' }}">
        <template x-for="n in notes" :key="n.id">
            <div class="rounded-lg border border-slate-200 bg-white px-4 py-3 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
                <template x-if="editingId !== n.id">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            <p class="whitespace-pre-line text-sm leading-snug text-slate-900" x-text="n.content"></p>
                            <div class="mt-1.5 flex flex-wrap items-center gap-x-1.5 text-xs text-slate-400">
                                <span class="font-medium text-brand-600" x-text="n.author"></span>
                                <span>·</span><span x-text="n.human"></span>
                                <span>·</span><span x-text="n.at"></span>
                                <template x-if="n.edited"><span class="text-slate-400">· düzenlendi <span x-text="n.edited"></span></span></template>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1 {{ $canEdit ? '' : 'hidden' }}">
                            <button type="button" @click="startEdit(n)" class="grid h-7 w-7 place-items-center rounded-md bg-slate-100 text-slate-600 transition hover:bg-slate-200" title="Düzenle"><i class="fa-solid fa-pen text-xs"></i></button>
                            @if ($canDelete)<button type="button" @click="remove(n)" class="grid h-7 w-7 place-items-center rounded-md bg-red-50 text-red-500 transition hover:bg-red-100" title="Sil"><i class="fa-solid fa-trash text-xs"></i></button>@endif
                        </div>
                    </div>
                </template>
                <template x-if="editingId === n.id">
                    <div class="space-y-3">
                        <textarea x-model="editText" rows="3" class="form-input" @keydown.meta.enter="saveEdit(n)" @keydown.ctrl.enter="saveEdit(n)"></textarea>
                        <div class="flex justify-end gap-2">
                            <button type="button" @click="editingId = null" class="btn btn-sm btn-secondary">Vazgeç</button>
                            <button type="button" @click="saveEdit(n)" class="btn btn-sm btn-success" :disabled="busy"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
                        </div>
                    </div>
                </template>
            </div>
        </template>
        <div x-show="notes.length === 0" class="rounded-lg border border-dashed border-slate-200 p-4 text-center text-xs text-slate-400">Henüz not yok.</div>
    </div>

    @if ($canEdit)
    <form @submit.prevent="add()" class="mt-3 flex items-center gap-2 border-t border-slate-200 pt-3">
        <input x-model="newText" type="text" required placeholder="Yeni not yaz..." class="form-input flex-1" @keydown.enter.prevent="add()">
        <button type="submit" class="grid h-[38px] w-[38px] shrink-0 place-items-center rounded-lg border border-slate-200 bg-white text-base text-slate-600 transition hover:border-brand-300 hover:bg-brand-50 hover:text-brand-700 disabled:opacity-40" :disabled="busy || !newText.trim()" title="Notu ekle"><i class="fa-solid fa-plus"></i></button>
    </form>
    <p x-show="error" x-cloak class="mt-2 text-xs text-red-600" x-text="error"></p>
    @endif
</div>
