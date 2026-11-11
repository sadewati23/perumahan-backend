@php
    $tema = $perumahan->status_theme;
@endphp
<article class="{{ $animasi ?? 'kartu-muncul' }} kartu-interaktif group relative flex h-full flex-col overflow-hidden rounded-xl border border-slate-200 border-t-4 {{ $tema['rail_top'] }} bg-white shadow-sm focus-within:ring-2 focus-within:ring-brand-600 focus-within:ring-offset-2">
    <div class="relative">
        <x-foto-perumahan :perumahan="$perumahan" class="aspect-[16/9] w-full" />
        <span class="absolute left-3 top-3 inline-flex items-center gap-1.5 rounded-full bg-white/95 px-2.5 py-1 text-xs font-semibold shadow-sm ring-1 ring-slate-200 {{ $perumahan->status_badge_class }}">
            <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
            {{ $perumahan->status_label }}
        </span>
    </div>

    <div class="flex flex-1 flex-col p-4 sm:p-5">
        <h3 class="text-base leading-snug">
            {{-- Satu-satunya tautan kartu; area klik diperluas ke seluruh kartu. --}}
            <a href="{{ route('publik.perumahan.show', $perumahan) }}"
               class="line-clamp-2 font-semibold text-slate-900 transition-colors after:absolute after:inset-0 after:z-10 after:content-[''] group-hover:text-brand-700">
                {{ $perumahan->nama }}
            </a>
        </h3>

        <p class="mt-1.5 flex items-start gap-1.5 text-sm leading-5 text-slate-600">
            <svg class="mt-0.5 h-4 w-4 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.4 7-11a7 7 0 10-14 0c0 4.6 7 11 7 11zm0-8.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z"/>
            </svg>
            <span>
                Kel. {{ $perumahan->kelurahan->nama ?? '-' }}, Kec. {{ $perumahan->kelurahan->kecamatan->nama ?? '-' }}
                @if ($perumahan->rt || $perumahan->rw)
                    <span class="block text-xs text-slate-500">RT {{ $perumahan->rt ?: '-' }} / RW {{ $perumahan->rw ?: '-' }}</span>
                @endif
            </span>
        </p>

        <dl class="mt-3 grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-sm">
            <div>
                <dt class="text-[11px] leading-4 text-slate-500">Jumlah unit</dt>
                <dd class="mt-0.5 font-semibold tabular-nums text-slate-900">{{ number_format($perumahan->jumlah_unit, 0, ',', '.') }}</dd>
            </div>
            <div>
                <dt class="text-[11px] leading-4 text-slate-500">Luas lahan</dt>
                <dd class="mt-0.5 font-semibold tabular-nums text-slate-900">{{ number_format((float) $perumahan->luas_lahan / 10000, 2, ',', '.') }} ha</dd>
            </div>
            <div>
                <dt class="text-[11px] leading-4 text-slate-500">Tahun selesai</dt>
                <dd class="mt-0.5 font-semibold tabular-nums text-slate-900">{{ $perumahan->tahun_selesai ?: '-' }}</dd>
            </div>
        </dl>

        <p class="mt-2 text-xs text-slate-500">
            Pengembang: {{ $perumahan->pengembang ?: 'tidak tercatat' }}
        </p>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1 border-t border-slate-100 bg-slate-50 px-4 py-2.5 text-[11px] text-slate-500 sm:px-5">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="whitespace-nowrap">RTH {{ number_format((float) $perumahan->luas_rth / 10000, 2, ',', '.') }} ha</span>
            <span class="whitespace-nowrap">KDB {{ $perumahan->kdb !== null ? (float) $perumahan->kdb . '%' : '-' }}</span>
            <span class="whitespace-nowrap">KDH {{ $perumahan->kdh !== null ? (float) $perumahan->kdh . '%' : '-' }}</span>
            @if ($perumahan->punya_koordinat)
                <span class="inline-flex items-center gap-1 whitespace-nowrap text-emerald-700">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    Peta
                </span>
            @endif
        </div>

        <span class="inline-flex shrink-0 items-center gap-1 whitespace-nowrap text-xs font-semibold text-brand-700 transition-colors group-hover:underline" aria-hidden="true">
            Lihat detail <span aria-hidden="true">&rarr;</span>
        </span>
    </div>
</article>
