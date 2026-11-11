<section id="tahun-pemakaman" class="scroll-mt-28 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 md:scroll-mt-36">
    <h2 class="text-base font-semibold text-slate-900">Waktu &amp; Lahan Pemakaman</h2>
    <dl class="mt-4 grid gap-4 sm:grid-cols-2">
        <div>
            <dt class="text-xs text-slate-500">Tahun pembuatan</dt>
            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->tahun_pembuatan ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs text-slate-500">Tahun selesai pembangunan</dt>
            <dd class="text-sm font-medium text-slate-800">{{ $perumahan->tahun_selesai ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-xs text-slate-500">Lahan pemakaman</dt>
            <dd class="text-sm font-medium {{ $perumahan->ada_lahan_pemakaman ? 'text-emerald-700' : 'text-slate-800' }}">
                {{ $perumahan->ada_lahan_pemakaman ? 'Tersedia' : 'Tidak tersedia' }}
            </dd>
        </div>
        @if ($perumahan->ada_lahan_pemakaman)
            <div>
                <dt class="text-xs text-slate-500">Luas lahan pemakaman</dt>
                <dd class="text-sm font-medium text-slate-800">
                    {{ $perumahan->luas_lahan_pemakaman !== null ? number_format((float) $perumahan->luas_lahan_pemakaman, 0, ',', '.') . ' m²' : '-' }}
                </dd>
            </div>
            <div class="sm:col-span-2">
                <dt class="text-xs text-slate-500">Lokasi lahan pemakaman</dt>
                <dd class="text-sm font-medium text-slate-800">{{ $perumahan->lokasi_lahan_pemakaman ?: '-' }}</dd>
            </div>
        @endif
    </dl>
</section>
