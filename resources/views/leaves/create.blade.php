<x-app-layout title="Yeni İzin / Devamsızlık" subtitle="Yıllık izin, ücretsiz izin, evlilik, doğum, vefat, rapor veya devamsızlık kaydı">
    <form method="POST" action="{{ route('leaves.store') }}" class="space-y-6">
        @csrf
        @include('leaves._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('leaves.index', ['year' => $leave->leave_year]) }}" class="btn-secondary">Vazgeç</a>
            <button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
        </div>
    </form>
</x-app-layout>
