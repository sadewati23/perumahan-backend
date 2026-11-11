<section id="psu" class="scroll-mt-28 rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6 md:scroll-mt-36">
    <div class="flex flex-wrap items-baseline justify-between gap-2">
        <h2 class="text-base font-semibold text-slate-900">Prasarana, Sarana &amp; Utilitas (PSU)</h2>
        <a href="{{ route('lapor.create', ['perumahan' => $perumahan->id]) }}"
           class="text-xs font-medium text-brand-700 hover:underline">
            Ada yang rusak? Laporkan &rarr;
        </a>
    </div>

    @if ($psuBermasalah > 0)
        <p class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-danger-50 px-2.5 py-1 text-xs font-semibold text-danger-700 ring-1 ring-inset ring-danger-200">
            <span class="h-1.5 w-1.5 rounded-full bg-danger-500" aria-hidden="true"></span>
            {{ $psuBermasalah }} PSU rusak / perlu perhatian
        </p>
    @endif

    @if ($perumahan->psu->isEmpty())
        <p class="mt-4 text-sm text-slate-500">Data PSU belum tersedia.</p>
    @else
        <div class="mt-4 overflow-x-auto">
            <table class="tabel-mini min-w-full divide-y divide-slate-200 text-sm">
                <thead class="text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="whitespace-nowrap py-2 pr-4">Jenis PSU</th>
                        <th class="whitespace-nowrap py-2 pr-4 text-right">Jumlah</th>
                        <th class="whitespace-nowrap py-2 pr-4">Kondisi</th>
                        <th class="whitespace-nowrap py-2">Keterangan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach ($perumahan->psu as $psu)
                        <tr class="{{ in_array($psu->kondisi, ['rusak_ringan', 'rusak_berat'], true) ? 'bg-danger-50/40' : '' }}">
                            <td data-label="Jenis PSU" class="py-3 pr-4 font-medium text-slate-700">{{ $psu->jenis }}</td>
                            <td data-label="Jumlah" class="py-3 pr-4 text-right text-slate-600">
                                {{ rtrim(rtrim(number_format((float) $psu->jumlah, 2, ',', '.'), '0'), ',') }} {{ $psu->satuan }}
                            </td>
                            <td data-label="Kondisi" class="py-3 pr-4 text-slate-600">
                                <span class="inline-flex items-center gap-1.5 whitespace-nowrap rounded-full px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $kelasKondisiPsu($psu->kondisi) }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-current" aria-hidden="true"></span>
                                    {{ $psu->label_kondisi }}
                                </span>
                            </td>
                            <td data-label="Keterangan" class="py-3 text-slate-500">{{ $psu->keterangan ?: '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
