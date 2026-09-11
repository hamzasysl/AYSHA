<x-app-layout :title="$employee->full_name" subtitle="Personel bilgilerini düzenle">
    <form method="POST" action="{{ route('employees.update', $employee) }}" class="space-y-6">
        @csrf @method('PUT')
        @include('employees._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('employees.show', $employee) }}" class="btn-secondary">Vazgeç</a>
            <button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Güncelle</button>
        </div>
    </form>
</x-app-layout>
