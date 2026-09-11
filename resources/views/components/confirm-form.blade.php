@props(['action', 'method' => 'DELETE', 'title' => 'Emin misiniz?', 'message' => 'Bu işlem geri alınamaz.', 'button' => 'Evet, Sil', 'redirect' => null, 'variant' => 'danger', 'fields' => []])
@php $confirmClass = $variant === 'success' ? 'btn btn-success' : 'btn btn-danger'; @endphp
<div x-data="{ open: false }" class="inline-block">
    <button type="button" @click="open = true" {{ $attributes->has('class') ? $attributes : $attributes->merge(['class' => $variant === 'success' ? 'btn-icon-primary' : 'btn-icon-danger']) }}>{{ $slot }}</button>
    <template x-teleport="body">
        <div x-show="open" @click.self="open = false" x-cloak x-transition.opacity class="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-sm rounded-xl border border-slate-200 bg-white p-6 shadow-xl">
                <h3 class="mb-2 text-lg font-semibold">{{ $title }}</h3>
                <p class="mb-5 text-sm text-slate-600">{{ $message }}</p>
                <form method="POST" action="{{ $action }}">
                    @csrf
                    @method($method)
                    @if ($redirect)<input type="hidden" name="redirect" value="{{ $redirect }}">@endif
                    @foreach ($fields as $k => $v)<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endforeach
                    @isset($extra){{ $extra }}@endisset
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="open = false" class="btn btn-secondary">Vazgeç</button>
                        <button type="submit" class="{{ $confirmClass }}">{{ $button }}</button>
                    </div>
                </form>
            </div>
        </div>
    </template>
</div>
