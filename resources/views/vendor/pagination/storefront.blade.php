@if ($paginator->hasPages())
    <nav class="sf-pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="sf-page-btn is-disabled" aria-disabled="true">‹</span>
        @else
            <a class="sf-page-btn" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous">‹</a>
        @endif

        <ul class="sf-page-list">
            @foreach ($elements as $element)
                @if (is_string($element))
                    <li><span class="sf-page-ellipsis">{{ $element }}</span></li>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li><span class="sf-page-num is-active" aria-current="page">{{ $page }}</span></li>
                        @else
                            <li><a class="sf-page-num" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach
        </ul>

        @if ($paginator->hasMorePages())
            <a class="sf-page-btn" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next">›</a>
        @else
            <span class="sf-page-btn is-disabled" aria-disabled="true">›</span>
        @endif
    </nav>
@endif
