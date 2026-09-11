@if (session('success') || session('error') || $errors->any())
    <div x-data="{ show: true }" x-show="show" x-transition class="space-y-2">
        @if (session('success'))
            <div class="flex items-start gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                <i class="fa-solid fa-circle-check mt-0.5"></i>
                <div class="flex-1">{{ session('success') }}</div>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-900"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
        @if (session('error'))
            <div class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                <div class="flex-1">{{ session('error') }}</div>
                <button @click="show = false" class="text-red-600 hover:text-red-900"><i class="fa-solid fa-xmark"></i></button>
            </div>
        @endif
        @if ($errors->any())
            <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                <div class="flex items-center gap-2 font-semibold"><i class="fa-solid fa-triangle-exclamation"></i> Lütfen formu kontrol edin:</div>
                <ul class="mt-1 list-disc pl-6">
                    @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif
    </div>
@endif
