<x-app-layout title="Yeni Personel" subtitle="Ekibe yeni bir kişi ekle">
    <form method="POST" action="{{ route('employees.store') }}" class="space-y-6">
        @csrf
        @include('employees._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('employees.index') }}" class="btn-secondary">Vazgeç</a>
            <button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
        </div>
    </form>
</x-app-layout>
