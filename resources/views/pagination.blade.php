@if($paginator->lastPage() > 1)
    <nav class="pagination" aria-label="Navigasi halaman" data-testid="pagination">
        @if($paginator->onFirstPage())
            <span class="page-link is-disabled">← Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}" class="page-link" rel="prev" data-testid="pagination-prev">← Sebelumnya</a>
        @endif

        @foreach($paginator->getUrlRange(max(1, $paginator->currentPage() - 2), min($paginator->lastPage(), $paginator->currentPage() + 2)) as $page => $url)
            @if($page == $paginator->currentPage())
                <span class="page-link is-active" data-testid="pagination-current">{{ $page }}</span>
            @else
                <a href="{{ $url }}" class="page-link">{{ $page }}</a>
            @endif
        @endforeach

        @if($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}" class="page-link" rel="next" data-testid="pagination-next">Berikutnya →</a>
        @else
            <span class="page-link is-disabled">Berikutnya →</span>
        @endif
    </nav>
@endif
