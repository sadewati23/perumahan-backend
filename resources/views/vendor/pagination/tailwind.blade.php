@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="mt-4">
        <div class="flex gap-2 items-center justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="inline-flex items-center rounded-control border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-400">
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex items-center rounded-control border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex items-center rounded-control border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50">
                    Berikutnya
                </a>
            @else
                <span class="inline-flex items-center rounded-control border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-400">
                    Berikutnya
                </span>
            @endif
        </div>

        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between sm:gap-4">
            <p class="text-sm text-slate-600">
                Menampilkan
                @if ($paginator->firstItem())
                    <span class="font-semibold text-slate-900">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-slate-900">{{ $paginator->lastItem() }}</span>
                @else
                    <span class="font-semibold text-slate-900">{{ $paginator->count() }}</span>
                @endif
                dari <span class="font-semibold text-slate-900">{{ $paginator->total() }}</span> data
            </p>

            <span class="inline-flex rounded-control shadow-card">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Sebelumnya" class="inline-flex items-center rounded-l-control border border-slate-300 bg-white px-2 py-2 text-slate-400">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"
                       class="inline-flex items-center rounded-l-control border border-slate-300 bg-white px-2 py-2 text-slate-600 transition hover:bg-slate-50">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true"
                              class="inline-flex items-center border border-l-0 border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-500">
                            {{ $element }}
                        </span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="inline-flex items-center border border-l-0 border-slate-300 bg-brand-50 px-4 py-2 text-sm font-semibold text-brand-700">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $url }}" aria-label="Ke halaman {{ $page }}"
                                   class="inline-flex items-center border border-l-0 border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                                    {{ $page }}
                                </a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"
                       class="inline-flex items-center rounded-r-control border border-l-0 border-slate-300 bg-white px-2 py-2 text-slate-600 transition hover:bg-slate-50">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Berikutnya" class="inline-flex items-center rounded-r-control border border-l-0 border-slate-300 bg-white px-2 py-2 text-slate-400">
                        <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"/>
                        </svg>
                    </span>
                @endif
            </span>
        </div>
    </nav>
@endif