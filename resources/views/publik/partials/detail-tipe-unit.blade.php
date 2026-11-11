<section id="unit" class="scroll-mt-28 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 md:scroll-mt-36">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-base font-semibold text-slate-900">Unit &amp; Tipe Rumah</h2>
        @if ($perumahan->tipeUnits->isNotEmpty())
            <span class="text-xs text-slate-500">
                Total {{ number_format($perumahan->total_unit_tipe, 0, ',', '.') }} unit terinci
            </span>
        @endif
    </div>

    @if ($perumahan->tipeUnits->isEmpty())
        <p class="mt-4 text-sm text-slate-500">Rincian tipe unit belum tersedia.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="tabel-mini min-w-full divide-y divide-slate-200 text-sm">
                <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap py-2 pr-4">Tipe</th>
                        <th class="whitespace-nowrap py-2 pr-4 text-right">Jumlah</th>
                        <th class="whitespace-nowrap py-2 pr-4 text-right">L. Tanah</th>
                        <th class="whitespace-nowrap py-2 pr-4 text-right">L. Bangunan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($perumahan->tipeUnits as $tipe)
                        <tr>
                            <td data-label="Tipe" class="py-3 pr-4 font-medium text-slate-700">{{ $tipe->tipe }}</td>
                            <td data-label="Jumlah" class="py-3 pr-4 text-right text-slate-600">{{ number_format($tipe->jumlah_unit, 0, ',', '.') }} unit</td>
                            <td data-label="L. Tanah" class="py-3 pr-4 text-right text-slate-600">{{ $tipe->luas_tanah !== null ? number_format((float) $tipe->luas_tanah, 0, ',', '.') . ' m²' : '-' }}</td>
                            <td data-label="L. Bangunan" class="py-3 pr-4 text-right text-slate-600">{{ $tipe->luas_bangunan !== null ? number_format((float) $tipe->luas_bangunan, 0, ',', '.') . ' m²' : '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
