@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfalama" class="flex items-center justify-between px-4 py-3 border-t border-slate-100">
        <div class="text-sm text-slate-600">
            <span class="font-medium">{{ $paginator->firstItem() }}</span>-<span class="font-medium">{{ $paginator->lastItem() }}</span>
            arası gösteriliyor, toplam <span class="font-medium">{{ $paginator->total() }}</span> kayıt
        </div>
        <div class="flex items-center gap-1">
            @if ($paginator->onFirstPage())
                <span class="btn btn-sm btn-secondary opacity-50 cursor-not-allowed"><i class="fa-solid fa-chevron-left"></i></span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="btn btn-sm btn-secondary"><i class="fa-solid fa-chevron-left"></i></a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="px-2 text-sm text-slate-400">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="btn btn-sm bg-brand-600 text-white">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" class="btn btn-sm btn-secondary">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="btn btn-sm btn-secondary"><i class="fa-solid fa-chevron-right"></i></a>
            @else
                <span class="btn btn-sm btn-secondary opacity-50 cursor-not-allowed"><i class="fa-solid fa-chevron-right"></i></span>
            @endif
        </div>
    </nav>
@endif
