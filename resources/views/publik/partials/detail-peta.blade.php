<section id="peta" class="scroll-mt-28 rounded-card border border-garis bg-white p-5 shadow-card sm:p-6 md:scroll-mt-36">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="font-judul text-xl font-semibold tracking-tight text-tinta">Titik Lokasi</h2>
        @if ($perumahan->punya_koordinat)
            <a href="{{ route('publik.peta', ['q' => $perumahan->nama]) }}"
               class="text-xs font-medium text-brand-700 hover:underline">
                Lihat di peta besar &rarr;
            </a>
        @endif
    </div>

    @if ($perumahan->punya_koordinat)
        <div id="peta-detail-publik"
             data-lat="{{ (float) $perumahan->latitude }}"
             data-lng="{{ (float) $perumahan->longitude }}"
             data-nama="{{ $perumahan->nama }}"
             data-zoom="{{ config('peta.zoom_detail') }}"
             data-tile-url="{{ config('peta.tile_url') }}"
             data-attribution="{{ config('peta.tile_attribution') }}"
             class="mt-3 h-64 w-full overflow-hidden rounded-lg border border-garis bg-slate-100"></div>
        <dl class="mt-3 grid grid-cols-2 gap-2 text-xs">
            <div>
                <dt class="text-slate-600">Latitude</dt>
                <dd class="font-medium tabular-nums text-slate-800">{{ $perumahan->latitude }}</dd>
            </div>
            <div>
                <dt class="text-slate-600">Longitude</dt>
                <dd class="font-medium tabular-nums text-slate-800">{{ $perumahan->longitude }}</dd>
            </div>
        </dl>
        <a href="https://www.openstreetmap.org/?mlat={{ $perumahan->latitude }}&mlon={{ $perumahan->longitude }}#map=17/{{ $perumahan->latitude }}/{{ $perumahan->longitude }}"
           target="_blank" rel="noopener"
           class="mt-3 inline-block text-xs font-medium text-brand-700 hover:underline">
            Buka di OpenStreetMap &rarr;
        </a>
    @else
        <p class="mt-3 text-sm text-slate-600">Titik koordinat perumahan ini belum tersedia.</p>
    @endif
</section>
