{{-- Paginasi khusus area admin. Halaman publik tetap memakai
     vendor/pagination/tailwind agar tampilannya tidak berubah. --}}
@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="mt-4">
        {{-- Layar kecil: hanya tombol sebelumnya / berikutnya --}}
        <div class="flex items-center justify-between gap-2 sm:hidden">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex min-h-11 items-center gap-1.5 rounded-control border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-400">
                    <x-icon name="panah-kiri" class="h-4 w-4"/>
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                   class="inline-flex min-h-11 items-center gap-1.5 rounded-control border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-kertas">
                    <x-icon name="panah-kiri" class="h-4 w-4"/>
                    Sebelumnya
                </a>
            @endif

            <span class="text-xs font-medium text-slate-600">Halaman {{ $paginator->currentPage() }}</span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                   class="inline-flex min-h-11 items-center gap-1.5 rounded-control border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-kertas">
                    Berikutnya
                    <x-icon name="panah-kanan" class="h-4 w-4"/>
                </a>
            @else
                <span aria-disabled="true" class="inline-flex min-h-11 items-center gap-1.5 rounded-control border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-400">
                    Berikutnya
                    <x-icon name="panah-kanan" class="h-4 w-4"/>
                </span>
            @endif
        </div>

        {{-- Layar sedang ke atas: jumlah data + nomor halaman --}}
        <div class="hidden flex-wrap items-center justify-between gap-3 sm:flex">
            <p class="text-sm text-slate-600">
                Menampilkan
                @if ($paginator->firstItem())
                    <span class="font-semibold text-tinta">{{ $paginator->firstItem() }}</span>–<span class="font-semibold text-tinta">{{ $paginator->lastItem() }}</span>
                @else
                    <span class="font-semibold text-tinta">{{ $paginator->count() }}</span>
                @endif
                dari <span class="font-semibold text-tinta">{{ $paginator->total() }}</span> data
            </p>

            <ul class="flex flex-wrap items-center gap-1">
                <li>
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Sebelumnya"
                              class="inline-flex h-9 w-9 items-center justify-center rounded-control border border-slate-200 bg-white text-slate-400">
                            <x-icon name="panah-kiri" class="h-4 w-4"/>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Sebelumnya"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-control border border-slate-300 bg-white text-slate-600 transition hover:bg-kertas">
                            <x-icon name="panah-kiri" class="h-4 w-4"/>
                        </a>
                    @endif
                </li>

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <li>
                            <span aria-disabled="true" class="inline-flex h-9 min-w-9 items-center justify-center px-2 text-sm font-medium text-slate-500">
                                {{ $element }}
                            </span>
                        </li>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            <li>
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page"
                                          class="inline-flex h-9 min-w-9 items-center justify-center rounded-control border border-brand-200 bg-brand-50 px-2 text-sm font-semibold text-brand-700">
                                        {{ $page }}
                                    </span>
                                @else
                                    <a href="{{ $url }}" aria-label="Ke halaman {{ $page }}"
                                       class="inline-flex h-9 min-w-9 items-center justify-center rounded-control border border-slate-300 bg-white px-2 text-sm font-medium text-slate-600 transition hover:bg-kertas">
                                        {{ $page }}
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    @endif
                @endforeach

                <li>
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Berikutnya"
                           class="inline-flex h-9 w-9 items-center justify-center rounded-control border border-slate-300 bg-white text-slate-600 transition hover:bg-kertas">
                            <x-icon name="panah-kanan" class="h-4 w-4"/>
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Berikutnya"
                              class="inline-flex h-9 w-9 items-center justify-center rounded-control border border-slate-200 bg-white text-slate-400">
                            <x-icon name="panah-kanan" class="h-4 w-4"/>
                        </span>
                    @endif
                </li>
            </ul>
        </div>
    </nav>
@endif
