<section class="border-b border-slate-200 bg-white">
    <div class="h-1 w-full {{ $tema['dot'] }}" aria-hidden="true"></div>
    <div class="mx-auto max-w-7xl px-4 py-6 lg:px-8">
        <x-page-header :title="$perumahan->nama" :breadcrumbs="[
            ['label' => 'Beranda', 'url' => route('publik.beranda')],
            ['label' => 'Data Perumahan', 'url' => route('publik.perumahan.index')],
            ['label' => $perumahan->nama],
        ]">
            <x-slot:actions>
                <a href="{{ route('lapor.create', ['perumahan' => $perumahan->id]) }}"
                   class="tombol-utama w-full sm:w-auto">
                    Lapor Kerusakan
                </a>
                <a href="{{ route('publik.peta') }}"
                   class="tombol-sekunder flex-1 sm:flex-none">
                    Peta Sebaran
                </a>
                <a href="{{ route('publik.perumahan.index') }}"
                   class="tombol-sekunder flex-1 sm:flex-none">
                    Daftar Perumahan
                </a>
            </x-slot:actions>
        </x-page-header>

        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $perumahan->status_badge_class }}">
                <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                {{ $perumahan->status_label }}
            </span>
            <p class="min-w-0 text-sm text-slate-600">{{ $perumahan->alamat_lengkap ?: 'Alamat belum dilengkapi' }}</p>
        </div>
        <p class="mt-1 text-xs text-slate-500">Pengembang: {{ $perumahan->pengembang ?: 'tidak tercatat' }}</p>

        @if ($perumahan->foto_url)
            <a href="{{ $perumahan->foto_url }}" data-lightbox="{{ $perumahan->foto_url }}"
               class="group kartu-muncul relative mt-5 block cursor-zoom-in overflow-hidden rounded-xl border border-slate-200"
               aria-label="Perbesar foto {{ $perumahan->nama }}">
                <x-foto-perumahan :perumahan="$perumahan" :lazy="false"
                                  class="h-64 w-full transition duration-500 group-hover:scale-[1.02] sm:h-80 md:h-96" />
                <span class="pointer-events-none absolute bottom-3 right-3 inline-flex items-center gap-1.5 rounded-full bg-slate-900/75 px-3 py-1.5 text-xs font-medium text-white backdrop-blur transition group-hover:bg-slate-900">
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35m2.1-5.4a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                    </svg>
                    Perbesar foto
                </span>
            </a>
        @endif

        <dl class="mt-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <dt class="text-xs font-medium text-slate-500">Luas lahan</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums text-slate-900">
                    <span data-hitung="{{ (float) $perumahan->luas_lahan }}" data-desimal="0">{{ number_format((float) $perumahan->luas_lahan, 0, ',', '.') }}</span> m&sup2;
                </dd>
                <p class="text-xs text-slate-500">{{ number_format($perumahan->luas_lahan_ha, 2, ',', '.') }} hektar</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <dt class="text-xs font-medium text-slate-500">Jumlah unit</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums text-slate-900">
                    <span data-hitung="{{ (int) $perumahan->jumlah_unit }}" data-desimal="0">{{ number_format($perumahan->jumlah_unit, 0, ',', '.') }}</span>
                </dd>
                <p class="text-xs text-slate-500">{{ $perumahan->tipeUnits->count() }} tipe unit</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <dt class="text-xs font-medium text-slate-500">Ruang terbuka hijau</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums text-slate-900">
                    <span data-hitung="{{ (float) $perumahan->luas_rth }}" data-desimal="0">{{ number_format((float) $perumahan->luas_rth, 0, ',', '.') }}</span> m&sup2;
                </dd>
                <p class="text-xs text-slate-500">{{ $perumahan->jumlah_rth }} titik RTH</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                <dt class="text-xs font-medium text-slate-500">KDB / KDH</dt>
                <dd class="mt-1 text-lg font-semibold tabular-nums text-slate-900">
                    {{ $perumahan->kdb !== null ? (float) $perumahan->kdb . '%' : '-' }} /
                    {{ $perumahan->kdh !== null ? (float) $perumahan->kdh . '%' : '-' }}
                </dd>
                <p class="text-xs text-slate-500">Koefisien dasar bangunan / hijau</p>
            </div>
        </dl>
    </div>
</section>

@if ($perumahan->foto_url)
    <dialog id="lightbox-foto" aria-label="Pratinjau foto perumahan"
            class="m-auto w-auto max-w-[92vw] bg-transparent p-0 backdrop:bg-slate-950/80">
        <div class="relative">
            <img data-lightbox-gambar alt="Foto {{ $perumahan->nama }} ukuran besar"
                 class="max-h-[85vh] w-auto max-w-full rounded-xl object-contain shadow-2xl">
            <button type="button" data-lightbox-tutup
                    class="absolute -right-2 -top-2 inline-flex h-10 w-10 items-center justify-center rounded-full bg-white text-slate-800 shadow-lg ring-1 ring-slate-200 transition hover:bg-slate-100 sm:-right-3 sm:-top-3"
                    aria-label="Tutup pratinjau foto">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                </svg>
            </button>
        </div>
    </dialog>
@endif
