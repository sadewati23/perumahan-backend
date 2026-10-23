@extends('layouts.publik')
@section('title', 'Status Laporan '.$laporan->kode)
@section('content')
<section class="mx-auto max-w-3xl px-4 py-8 lg:px-8">
<x-card>
<div class="flex flex-wrap items-center justify-between gap-3"><h1 class="text-xl font-semibold">Status Laporan</h1><x-badge tone="neutral">{{ $laporan->kode }}</x-badge></div>
<dl class="mt-6 grid gap-4 sm:grid-cols-2"><div><dt class="text-xs text-slate-500">Jenis</dt><dd>{{ $laporan->label_jenis }}</dd></div><div><dt class="text-xs text-slate-500">Judul</dt><dd>{{ $laporan->judul }}</dd></div><div><dt class="text-xs text-slate-500">Lokasi</dt><dd>{{ $laporan->lokasi_ringkas }}</dd></div><div><dt class="text-xs text-slate-500">Tanggal dikirim</dt><dd>{{ $laporan->created_at->translatedFormat('d F Y, H:i') }} WIB</dd></div><div><dt class="text-xs text-slate-500">Terakhir diperbarui</dt><dd>{{ $laporan->updated_at->translatedFormat('d F Y, H:i') }} WIB</dd></div></dl>
<div class="mt-6 border-t border-slate-100 pt-5"><p class="text-xs text-slate-500">Status</p><x-badge class="mt-1" :tone="match($laporan->status) { 'selesai' => 'success', 'ditolak' => 'danger', 'diproses' => 'info', default => 'warning' }">{{ $laporan->label_status }}</x-badge><p class="mt-3 text-sm text-slate-600">{{ match($laporan->status) { 'baru' => 'Laporan telah diterima dan menunggu peninjauan.', 'diproses' => 'Laporan sedang ditindaklanjuti oleh petugas.', 'selesai' => 'Laporan telah selesai ditindaklanjuti.', 'ditolak' => 'Laporan tidak dapat ditindaklanjuti.', default => 'Status laporan diperbarui.' } }}</p></div>
<div class="mt-5 rounded-lg bg-slate-50 p-4 text-sm"><p class="font-semibold">Tahap penanganan</p><p class="mt-1 text-slate-600">{{ $laporan->status === 'ditolak' ? 'Diterima → Ditolak' : 'Diterima → Diproses → Selesai' }}</p></div>
 @if ($laporan->status === 'selesai' && filled($laporan->deskripsi_aksi_selesai))<div class="mt-5"><h2 class="font-semibold">Tindak lanjut</h2><p class="mt-1 whitespace-pre-line text-sm text-slate-600">{{ $laporan->deskripsi_aksi_selesai }}</p></div>@endif
 @if ($laporan->fotosPerbaikan->isNotEmpty())
 <div class="mt-5 border-t border-slate-100 pt-5">
     <h2 class="font-semibold">Foto Bukti Perbaikan</h2>
     <p class="mt-1 text-sm text-slate-500">Dokumentasi hasil penanganan laporan.</p>
     <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3">
         @foreach ($laporan->fotosPerbaikan as $foto)
             <a href="{{ $foto->url }}" target="_blank" rel="noopener" class="group block overflow-hidden rounded-lg border border-slate-200">
                 <img src="{{ $foto->url }}" alt="Foto bukti perbaikan {{ $laporan->kode }} #{{ $loop->iteration }}" loading="lazy" class="h-32 w-full object-cover transition group-hover:scale-105">
                 @if ($foto->keterangan)
                     <span class="block px-2 py-1.5 text-xs text-slate-600">{{ $foto->keterangan }}</span>
                 @endif
             </a>
         @endforeach
     </div>
 </div>
 @endif
@if ($laporan->jatuh_tempo_hapus)<p class="mt-5 rounded-lg bg-amber-50 p-3 text-sm text-amber-800">Laporan ini akan dihapus otomatis pada {{ $laporan->jatuh_tempo_hapus->translatedFormat('d F Y, H:i') }} WIB.</p>@endif
<a href="{{ route('cek-laporan.index') }}" class="mt-6 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white">Cek laporan lain</a>
</x-card></section>
@endsection
