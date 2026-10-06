@extends('layouts.app')

@section('title', 'Laporan Warga')
@section('subtitle', 'Kerusakan sarana-prasarana, pengaduan, dan bangunan liar dari warga')

@section('content')
    @php
        $chipStatus = [
            '' => ['label' => 'Semua', 'warna' => 'bg-slate-900 text-white'],
            'baru' => ['label' => 'Baru', 'warna' => 'bg-amber-100 text-amber-800 ring-amber-600/20'],
            'diproses' => ['label' => 'Diproses', 'warna' => 'bg-sky-100 text-sky-800 ring-sky-600/20'],
            'selesai' => ['label' => 'Selesai', 'warna' => 'bg-emerald-100 text-emerald-800 ring-emerald-600/20'],
            'ditolak' => ['label' => 'Ditolak', 'warna' => 'bg-rose-100 text-rose-800 ring-rose-600/20'],
        ];
    @endphp

    <div class="flex flex-wrap gap-3">
        @foreach ($chipStatus as $status => $chip)
            <a href="{{ route('laporan.index', array_filter(['q' => $filters['q'] ?? null, 'jenis' => $filters['jenis'] ?? null, 'tingkat' => $filters['tingkat'] ?? null, 'perumahan_id' => $filters['perumahan_id'] ?? null, 'status' => $status])) }}"
               class="flex-1 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-indigo-300
                      {{ ($filters['status'] ?? '') === $status ? 'ring-2 ring-indigo-500/40' : '' }}">
                <p class="text-xs font-medium text-slate-500">{{ $chip['label'] }}</p>
                <p class="mt-1 text-2xl font-semibold text-slate-900">
                    {{ $status === '' ? $ringkasan['total'] : $ringkasan[$status] }}
                </p>
            </a>
        @endforeach
    </div>

    <div class="mt-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.index') }}" class="flex flex-wrap items-end gap-3">
            @if ($filters['status'] ?? '')
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
            @endif

            <div class="min-w-48 flex-1">
                <label for="q" class="mb-1.5 block text-sm font-medium text-slate-700">Cari</label>
                <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}"
                       placeholder="Kode tiket, judul, pelapor, atau perumahan…"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
            </div>

            <div class="min-w-40">
                <label for="jenis" class="mb-1.5 block text-sm font-medium text-slate-700">Jenis</label>
                <select id="jenis" name="jenis"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Laporan::JENIS as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['jenis'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-40">
                <label for="perumahan_id" class="mb-1.5 block text-sm font-medium text-slate-700">Perumahan</label>
                <select id="perumahan_id" name="perumahan_id"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Semua</option>
                    @foreach ($perumahans as $item)
                        <option value="{{ $item->id }}" @selected((string) ($filters['perumahan_id'] ?? '') === (string) $item->id)>{{ $item->nama }}</option>
                    @endforeach
                </select>
            </div>

            <div class="min-w-36">
                <label for="tingkat" class="mb-1.5 block text-sm font-medium text-slate-700">Tingkat</label>
                <select id="tingkat" name="tingkat"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <option value="">Semua</option>
                    @foreach (\App\Models\Laporan::TINGKAT as $value => $label)
                        <option value="{{ $value }}" @selected(($filters['tingkat'] ?? '') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <button type="submit"
                    class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800">
                Terapkan
            </button>
            <a href="{{ route('laporan.index') }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                Reset
            </a>
        </form>
    </div>

    <div class="mt-4 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="whitespace-nowrap px-4 py-3">Kode</th>
                        <th class="whitespace-nowrap px-4 py-3">Laporan</th>
                        <th class="whitespace-nowrap px-4 py-3">Jenis</th>
                        <th class="whitespace-nowrap px-4 py-3">Pelapor</th>
                        <th class="whitespace-nowrap px-4 py-3">Tingkat</th>
                        <th class="whitespace-nowrap px-4 py-3">Ukuran</th>
                        <th class="whitespace-nowrap px-4 py-3">Status</th>
                        <th class="whitespace-nowrap px-4 py-3">Tanggal</th>
                        <th class="whitespace-nowrap px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($laporans as $laporan)
                        <tr class="transition hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-slate-700">{{ $laporan->kode }}</td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $laporan->judul }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $laporan->lokasi_ringkas }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $laporan->badge_jenis }}">{{ $laporan->label_jenis }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <p class="text-slate-700">{{ $laporan->nama_pelapor }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $laporan->kontak_pelapor }}</p>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if ($laporan->label_tingkat)
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $laporan->badge_tingkat }}">{{ $laporan->label_tingkat }}</span>
                                @else
                                    <span class="text-xs text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-600">{{ $laporan->ukuran_ringkas }}</td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span class="rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $laporan->badge_status }}">{{ $laporan->label_status }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs text-slate-500">
                                {{ $laporan->created_at->translatedFormat('d M Y, H:i') }}
                            </td>                        <td class="whitespace-nowrap px-4 py-3 text-right">
                            <button type="button"
                                    data-confirm="Hapus laporan {{ $laporan->kode }} ini?
                                                    Data laporan, foto buktinya, dan penanganan akan dihapus permanen dan tidak bisa dikembalikan."
                                    data-action="{{ route('laporan.destroy', $laporan) }}"
                                    class="rounded-lg border border-rose-300 px-3 py-1.5 text-xs font-semibold text-rose-600 transition hover:bg-rose-50">
                                Hapus
                            </button>
                        </td>
                    </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-sm text-slate-500">
                                Belum ada laporan @if ($filters['status'] ?? $filters['q'] ?? $filters['perumahan_id'] ?? $filters['tingkat'] ?? $filters['jenis'] ?? null) yang cocok dengan filter @endif .
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($laporans->hasPages())
            <div class="border-t border-slate-100 px-4 py-3">
                {{ $laporans->links() }}
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelectorAll('[data-confirm]').forEach((button) => {
                button.addEventListener('click', () => {
                    if (!window.confirm(button.dataset.confirm)) {
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = button.dataset.action;

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = '{{ csrf_token() }}';
                    form.appendChild(csrf);

                    const method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    method.value = 'DELETE';
                    form.appendChild(method);

                    document.body.appendChild(form);
                    form.submit();
                });
            });
        });
    </script>
@endpush
