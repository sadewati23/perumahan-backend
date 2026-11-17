@extends('layouts.admin')

@section('title', 'Impor Data Perumahan')
@section('subtitle', 'Tambah banyak data perumahan sekaligus dari file CSV atau Excel')

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <h2 class="font-judul text-2xl font-semibold tracking-tight text-tinta">Unggah file data perumahan</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600">
                Format yang diterima: <strong class="text-slate-700">CSV, XLS, dan XLSX</strong> (maksimal 4 MB / 500 baris).
                Semua baris divalidasi lebih dulu — bila masih ada baris yang bermasalah, tidak ada data yang tersimpan.
            </p>
        </div>
        <x-button href="{{ route('perumahan.index') }}" variant="secondary">
            <x-icon name="panah-kiri" class="h-4 w-4"/>
            Kembali
        </x-button>
    </div>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <x-card heading="1. Unduh template" meta="Isi data di bawah baris judul kolom">
            <p class="text-sm text-slate-600">
                Baris kedua berisi contoh isi — hapus atau ganti sebelum diimpor.
            </p>
            <div class="mt-3 flex flex-wrap gap-2">
                <x-button href="{{ route('perumahan.import.template-csv') }}" variant="secondary" size="sm">
                    <x-icon name="berkas" class="h-4 w-4"/>
                    Template CSV
                </x-button>
                <x-button href="{{ route('perumahan.import.template-xlsx') }}" variant="secondary" size="sm">
                    <x-icon name="berkas" class="h-4 w-4"/>
                    Template Excel
                </x-button>
            </div>
        </x-card>

        <x-card heading="2. Kolom wajib diisi" meta="Tanpa kolom ini, baris akan ditolak">
            <div class="flex flex-wrap gap-1.5">
                @foreach (['Nama Perumahan', 'Kelurahan', 'Luas Lahan', 'Jumlah Unit'] as $kolom)
                    <span class="inline-flex items-center rounded-pill bg-brand-50 px-2.5 py-1 text-xs font-medium text-brand-700 ring-1 ring-inset ring-brand-200">
                        {{ $kolom }}
                    </span>
                @endforeach
            </div>
            <p class="mt-3 text-xs leading-relaxed text-slate-600">
                Nama kelurahan harus yang sudah terdaftar di menu Wilayah. Bila nama kelurahan sama di beberapa
                kecamatan, isi juga kolom <strong class="text-slate-700">Kecamatan</strong>. Kolom status boleh diisi
                "Subsidi", "Komersil", atau "Campuran" (huruf besar/kecil bebas) — bila dikosongkan
                dianggap "Komersil".
            </p>
        </x-card>
    </div>

    <x-card heading="3. Unggah file" meta="CSV, XLS, atau XLSX — maksimal 4 MB" class="mt-4">
        <form method="POST" action="{{ route('perumahan.import.simpan') }}"
              enctype="multipart/form-data" data-unsaved-guard="File impor sudah dipilih tapi belum diproses."
              class="space-y-4">
            @csrf

            <div>
                <label for="file" class="label mb-1.5">File data perumahan</label>
                <input id="file" type="file" name="file" accept=".csv,.xls,.xlsx"
                       class="input cursor-pointer file:mr-3 file:cursor-pointer file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-brand-700 hover:file:bg-brand-100">
                <p class="mt-1.5 text-xs text-slate-600">Baris pertama pada file wajib berisi judul kolom; pertahankan nama judul persis seperti template.</p>
                <p class="mt-1 text-xs text-slate-600">Bagian PSU dan tipe unit belum termasuk — isi lewat halaman edit perumahan setelah datanya tersimpan.</p>
            </div>

            <x-button type="submit">
                <x-icon name="unggah" class="h-4 w-4"/>
                Mulai impor
            </x-button>
        </form>
    </x-card>
@endsection
