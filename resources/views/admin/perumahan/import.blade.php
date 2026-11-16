@extends('layouts.admin')

@section('title', 'Impor Data Perumahan')
@section('subtitle', 'Tambah banyak data perumahan sekaligus dari file CSV atau Excel')

@section('content')
    <div class="rounded-card border border-garis bg-white p-4 shadow-card lg:p-6">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="font-judul text-2xl font-semibold tracking-tight text-tinta">Unggah file data perumahan</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Format yang diterima: <strong>CSV, XLS, dan XLSX</strong> (maksimal 4 MB / 500 baris).
                    Semua baris divalidasi lebih dulu — bila masih ada baris yang bermasalah, tidak ada data yang tersimpan.
                </p>
            </div>
            <a href="{{ route('perumahan.index') }}" class="tombol-sekunder">
                &larr; Kembali
            </a>
        </div>

        <div class="mb-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-card border border-garis bg-kertas p-4">
                <p class="text-sm font-semibold text-tinta">1. Unduh template</p>
                <p class="mt-1 text-sm text-slate-600">Isi data di bawah baris judul kolom. Baris kedua berisi contoh isi — hapus atau ganti sebelum diimpor.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('perumahan.import.template-csv') }}" class="tombol-sekunder">
                        Template CSV
                    </a>
                    <a href="{{ route('perumahan.import.template-xlsx') }}" class="tombol-sekunder">
                        Template Excel
                    </a>
                </div>
            </div>
            <div class="rounded-card border border-garis bg-kertas p-4">
                <p class="text-sm font-semibold text-tinta">2. Kolom wajib diisi</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-garis">Nama Perumahan</span>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-garis">Kelurahan</span>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-garis">Luas Lahan</span>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-slate-700 ring-1 ring-garis">Jumlah Unit</span>
                </div>
                <p class="mt-2 text-xs text-slate-600">
                    Nama kelurahan harus yang sudah terdaftar di menu Wilayah. Bila nama kelurahan sama di beberapa
                    kecamatan, isi juga kolom <strong>Kecamatan</strong>. Kolom status boleh diisi
                    "Subsidi", "Komersil", atau "Campuran" (huruf besar/kecil bebas) — bila dikosongkan
                    dianggap "Komersil".
                </p>
            </div>
        </div>

        <div class="rounded-card border border-garis bg-white p-5 shadow-card">
            <form method="POST" action="{{ route('perumahan.import.simpan') }}"
                  enctype="multipart/form-data" data-unsaved-guard="File impor sudah dipilih tapi belum diproses.">
                @csrf

                <div class="mb-4">
                    <label for="file" class="mb-1 block text-sm font-semibold text-slate-700">File data perumahan</label>
                    <input id="file" type="file" name="file" accept=".csv,.xls,.xlsx"
                           class="w-full cursor-pointer rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-brand-50 file:px-3 file:py-1.5 file:text-brand-700 transition focus:border-brand-500 focus:ring-2 focus:ring-brand-500/20">
                    <p class="mt-1 text-xs text-slate-600">Baris pertama pada file wajib berisi judul kolom; pertahankan nama judul persis seperti template.</p>
                    <p class="mt-1 text-xs text-slate-600">Bagian PSU dan tipe unit belum termasuk — isi lewat halaman edit perumahan setelah datanya tersimpan.</p>
                </div>

                <button type="submit" class="tombol-utama">
                    Mulai impor
                </button>
            </form>
        </div>
    </div>
@endsection
