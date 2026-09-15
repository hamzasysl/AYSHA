{{--
    Bakım sayfası: Ayarlar sayfası herhangi bir nedenle açılmazsa (örn. veritabanı
    güncellemesi yapılmadığı için) güncelleme düğmelerine buradan erişilir.
--}}
<x-app-layout title="Sistem" subtitle="Güncelleme ve önbellek" section="Ayarlar">
    <x-slot:actions>
        <a href="{{ route('settings.edit', ['tab' => 'system']) }}" class="btn-secondary btn-sm"><i class="fa-solid fa-sliders"></i> Ayarlar</a>
    </x-slot:actions>

    <div class="space-y-4">
        @include('settings._system')
    </div>
</x-app-layout>
