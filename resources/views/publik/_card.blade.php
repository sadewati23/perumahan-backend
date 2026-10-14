<article class="flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md">
    <div class="aspect-[16/9] w-full overflow-hidden bg-slate-100">
        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='640' height='360' viewBox='0 0 640 360'%3E%3Crect width='640' height='360' fill='%23e2e8f0'/%3E%3Cpath d='M220 240l80-90 70 80h-150zm160 0l-50-55-40 45h-70l65-75 40 45 70-80 105 120h-120z' fill='%2394a3b8'/%3E%3Ctext x='320' y='200' font-family='Instrument Sans,Arial' font-size='20' fill='%2364748b' text-anchor='middle'%3EPerumahan%3C/text%3E%3C/svg%3E"
             alt="Ilustrasi perumahan" width="640" height="360" loading="lazy" class="h-full w-full object-cover">
    </div>
    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
            <a href="{{ route('publik.perumahan.show', $perumahan) }}" class="font-semibold text-slate-900 hover:text-indigo-600">
                {{ $perumahan->nama }}
            </a>
            <p class="mt-0.5 text-xs text-slate-500">{{ $perumahan->pengembang ?: 'Pengembang tidak tercatat' }}</p>
        </div>
        <span class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $perumahan->badge_status }}">
            {{ $perumahan->label_status }}
        </span>
    </div>

    <div class="flex-1 px-5 py-4">
        <p class="flex items-start gap-2 text-sm text-slate-600">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-indigo-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.4 7-11a7 7 0 10-14 0c0 4.6 7 11 7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/>
            </svg>
            <span>
                Kel. {{ $perumahan->kelurahan->nama ?? '-' }}, Kec. {{ $perumahan->kelurahan->kecamatan->nama ?? '-' }}
                @if ($perumahan->rt || $perumahan->rw)
                    <span class="block text-xs text-slate-500">RT {{ $perumahan->rt ?: '-' }} / RW {{ $perumahan->rw ?: '-' }}</span>
                @endif
            </span>
        </p>

        <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
            <div>
                <dt class="text-xs text-slate-500">Luas lahan</dt>
                <dd class="font-medium text-slate-800">{{ number_format((float) $perumahan->luas_lahan / 10000, 2, ',', '.') }} ha</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">Jumlah unit</dt>
                <dd class="font-medium text-slate-800">{{ number_format($perumahan->jumlah_unit, 0, ',', '.') }} unit</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">Luas RTH</dt>
                <dd class="font-medium text-slate-800">{{ number_format((float) $perumahan->luas_rth / 10000, 2, ',', '.') }} ha</dd>
            </div>
            <div>
                <dt class="text-xs text-slate-500">Selesai dibangun</dt>
                <dd class="font-medium text-slate-800">{{ $perumahan->tahun_selesai ?: '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-slate-50/70 px-5 py-3">
        <div class="flex min-w-0 items-center gap-3 text-xs text-slate-500">
            <span class="whitespace-nowrap">KDB {{ $perumahan->kdb !== null ? (float) $perumahan->kdb . '%' : '-' }}</span>
            <span class="whitespace-nowrap">KDH {{ $perumahan->kdh !== null ? (float) $perumahan->kdh . '%' : '-' }}</span>
            @if ($perumahan->punya_koordinat)
                <span class="inline-flex items-center gap-1 text-emerald-600">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Peta
                </span>
            @endif
        </div>

        <a href="{{ route('publik.perumahan.show', $perumahan) }}"
           class="shrink-0 whitespace-nowrap text-sm font-semibold text-indigo-600 hover:text-indigo-700">
            Detail &rarr;
        </a>
    </div>
</article>
