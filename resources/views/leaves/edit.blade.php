<x-app-layout title="İzin Kaydını Düzenle" :subtitle="$leave->employee->full_name.' · '.$leave->type_label.' · '.$leave->start_date->format('d.m.Y')">
    <x-slot name="actions">
        <a href="{{ route('leaves.print', $leave) }}" target="_blank" class="btn-secondary btn-sm"><i class="fa-solid fa-print"></i> İzin Formu</a>
    </x-slot>
    <form method="POST" action="{{ route('leaves.update', $leave) }}" class="space-y-6">
        @csrf @method('PUT')
        @include('leaves._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('leaves.index', ['year' => $leave->leave_year]) }}" class="btn-secondary">Vazgeç</a>
            <button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Güncelle</button>
        </div>
    </form>
</x-app-layout>
