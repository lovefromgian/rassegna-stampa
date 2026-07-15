@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Navigazione pagine">
        {{-- Precedente --}}
        @if ($paginator->onFirstPage())
            <span class="pagination-link disabled" aria-disabled="true">‹ Prec.</span>
        @else
            <button type="button" class="pagination-link" rel="prev"
                    wire:click="previousPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled">‹ Prec.</button>
        @endif

        {{-- Numeri di pagina --}}
        @foreach ($elements as $element)
            @if (is_string($element))
                <span class="pagination-link disabled">{{ $element }}</span>
            @endif

            @if (is_array($element))
                @foreach ($element as $page => $url)
                    @if ($page == $paginator->currentPage())
                        <span class="pagination-link current" aria-current="page">{{ $page }}</span>
                    @else
                        <button type="button" class="pagination-link"
                                wire:click="gotoPage({{ $page }}, '{{ $paginator->getPageName() }}')">{{ $page }}</button>
                    @endif
                @endforeach
            @endif
        @endforeach

        {{-- Successiva --}}
        @if ($paginator->hasMorePages())
            <button type="button" class="pagination-link" rel="next"
                    wire:click="nextPage('{{ $paginator->getPageName() }}')"
                    wire:loading.attr="disabled">Succ. ›</button>
        @else
            <span class="pagination-link disabled" aria-disabled="true">Succ. ›</span>
        @endif
    </nav>
@endif
