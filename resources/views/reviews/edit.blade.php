<x-app-layout title="Değerlendirmeyi Düzenle" :subtitle="$review->employee->full_name.' · '.$review->period_label">
    <form method="POST" action="{{ route('reviews.update', $review) }}" class="space-y-6">
        @csrf @method('PUT')
        @include('reviews._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('reviews.index', ['year' => $review->period_year, 'month' => $review->period_month]) }}" class="btn-secondary">Vazgeç</a>
            <button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Güncelle</button>
        </div>
    </form>
</x-app-layout>
