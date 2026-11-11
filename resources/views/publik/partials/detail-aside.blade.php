{{-- Kolom samping menempel di desktop; di HP mengalir di bawah konten. --}}
<aside class="kartu-muncul space-y-6 lg:sticky lg:top-32 lg:self-start">
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
        <h2 class="text-base font-semibold text-slate-900">Perumahan Lain di Kecamatan Ini</h2>
        <ul class="mt-3 divide-y divide-slate-100">
            @forelse ($lainnya as $item)
                <li class="py-3">
                    <a href="{{ route('publik.perumahan.show', $item) }}" class="block">
                        <p class="text-sm font-medium text-slate-800 hover:text-brand-700">{{ $item->nama }}</p>
                        <p class="text-xs text-slate-500">
                            {{ $item->status_label }} &middot; {{ $item->jumlah_unit }} unit &middot;
                            Kel. {{ $item->kelurahan->nama ?? '-' }}
                        </p>
                    </a>
                </li>
            @empty
                <li class="py-3 text-sm text-slate-500">Belum ada perumahan lain di kecamatan ini.</li>
            @endforelse
        </ul>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-5 text-xs text-slate-500 shadow-sm sm:p-6">
        <p>Terakhir diperbarui: {{ $perumahan->updated_at?->translatedFormat('d F Y, H:i') ?? '-' }}</p>
        <p class="mt-2">
            Data bersumber dari pendataan petugas. Menemukan data yang perlu diperbarui?
            Silakan hubungi petugas pendataan wilayah Anda.
        </p>
    </div>
</aside>
