@php $tema = $perumahan->status_theme; @endphp
<article class="{{ $animasi ?? 'kartu-muncul' }} kartu-interaktif group flex flex-col overflow-hidden rounded-2xl border border-x-slate-200 border-b-slate-200 border-t-4 {{ $tema['rail_top'] }} bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
    <div class="relative overflow-hidden">
        <x-foto-perumahan :perumahan="$perumahan" class="aspect-[16/9] w-full transition duration-500 group-hover:scale-105" />
        <span class="absolute right-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-3 py-1.5 text-xs font-semibold shadow-sm ring-1 ring-slate-200 {{ $perumahan->status_badge_class }}">
            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
            {{ $perumahan->status_label }}
        </span>
    </div>
    <div class="flex items-start justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div class="min-w-0">
            <a href="{{ route('publik.perumahan.show', $perumahan) }}" class="line-clamp-2 font-semibold leading-5 text-slate-900 hover:text-indigo-600">
                {{ $perumahan->nama }}
            </a>
            <p class="mt-0.5 text-xs text-slate-500">{{ $perumahan->pengembang ?: 'Pengembang tidak tercatat' }}</p>
        </div>
    </div>

    <div class="flex-1 px-5 py-4">
        <p class="flex items-start gap-2 text-sm leading-5 text-slate-600">
            <svg class="mt-0.5 h-4 w-4 shrink-0 {{ $tema['text'] }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.4 7-11a7 7 0 10-14 0c0 4.6 7 11 7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/>
            </svg>
            <span>
                Kel. {{ $perumahan->kelurahan->nama ?? '-' }}, Kec. {{ $perumahan->kelurahan->kecamatan->nama ?? '-' }}
                @if ($perumahan->rt || $perumahan->rw)
                    <span class="block text-xs text-slate-500">RT {{ $perumahan->rt ?: '-' }} / RW {{ $perumahan->rw ?: '-' }}</span>
                @endif
            </span>
        </p>

        <dl class="mt-5 grid grid-cols-2 gap-2 text-sm">
            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                <dt class="text-xs text-slate-500">Luas lahan</dt>
                <dd class="font-semibold {{ $tema['text'] }}">{{ number_format((float) $perumahan->luas_lahan / 10000, 2, ',', '.') }} ha</dd>
            </div>
            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                <dt class="text-xs text-slate-500">Jumlah unit</dt>
                <dd class="font-semibold {{ $tema['text'] }}">{{ number_format($perumahan->jumlah_unit, 0, ',', '.') }} unit</dd>
            </div>
            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                <dt class="text-xs text-slate-500">Luas RTH</dt>
                <dd class="font-semibold {{ $tema['text'] }}">{{ number_format((float) $perumahan->luas_rth / 10000, 2, ',', '.') }} ha</dd>
            </div>
            <div class="rounded-lg bg-slate-50 px-3 py-2.5">
                <dt class="text-xs text-slate-500">Selesai dibangun</dt>
                <dd class="font-semibold {{ $tema['text'] }}">{{ $perumahan->tahun_selesai ?: '-' }}</dd>
            </div>
        </dl>
    </div>

    <div class="flex items-center justify-between gap-3 border-t border-slate-100 bg-gradient-to-r {{ $tema['gradient'] }} px-5 py-3">
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
           class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-semibold {{ $tema['text'] }} transition hover:bg-white/80 hover:underline">
            Lihat detail <span aria-hidden="true">&rarr;</span>
        </a>
    </div>
</article>
