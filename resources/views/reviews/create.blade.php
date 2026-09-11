<x-app-layout title="Yeni Değerlendirme" :subtitle="$review->period_label.' dönemi · personel performansını puanla'">
    <form method="POST" action="{{ route('reviews.store') }}" class="space-y-6">
        @csrf
        @include('reviews._form')
        <div class="flex justify-end gap-2">
            <a href="{{ route('reviews.index', ['year' => $review->period_year, 'month' => $review->period_month]) }}" class="btn-secondary">Vazgeç</a>
            <button class="btn-success"><i class="fa-solid fa-floppy-disk"></i> Kaydet</button>
        </div>
    </form>
</x-app-layout>
