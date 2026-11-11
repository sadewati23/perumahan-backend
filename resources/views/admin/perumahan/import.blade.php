@extends('layouts.admin')

@section('title', 'Impor Data Perumahan')
@section('subtitle', 'Tambah banyak data perumahan sekaligus dari file CSV atau Excel')

@section('content')
    <div class="rounded-2xl bg-gradient-to-br from-slate-50 via-white to-slate-100 p-4 shadow-sm ring-1 ring-slate-200/70 lg:p-6">
        <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Unggah file data perumahan</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Format yang diterima: <strong>CSV, XLS, dan XLSX</strong> (maksimal 4 MB / 500 baris).
                    Semua baris divalidasi lebih dulu — bila masih ada baris yang bermasalah, tidak ada data yang tersimpan.
                </p>
            </div>
            <a href="{{ route('perumahan.index') }}"
               class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50">
                &larr; Kembali
            </a>
        </div>

        <div class="mb-5 grid gap-3 sm:grid-cols-2">
            <div class="rounded-xl border border-sky-200 bg-sky-50 p-4">
                <p class="text-sm font-semibold text-sky-900">1. Unduh template</p>
                <p class="mt-1 text-sm text-sky-800">Isi data di bawah baris judul kolom. Baris kedua berisi contoh isi — hapus atau ganti sebelum diimpor.</p>
                <div class="mt-3 flex flex-wrap gap-2">
                    <a href="{{ route('perumahan.import.template-csv') }}"
                       class="rounded-lg border border-sky-300 bg-white px-4 py-2 text-sm font-semibold text-sky-800 transition hover:bg-sky-100">
                        ⬇ Template CSV
                    </a>
                    <a href="{{ route('perumahan.import.template-xlsx') }}"
                       class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-sky-700">
                        ⬇ Template Excel
                    </a>
                </div>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">
                <p class="text-sm font-semibold text-amber-900">2. Kolom wajib diisi</p>
                <div class="mt-2 flex flex-wrap gap-2">
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-amber-900 ring-1 ring-amber-200">Nama Perumahan</span>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-amber-900 ring-1 ring-amber-200">Kelurahan</span>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-amber-900 ring-1 ring-amber-200">Luas Lahan</span>
                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-amber-900 ring-1 ring-amber-200">Jumlah Unit</span>
                </div>
                <p class="mt-2 text-xs text-amber-800">
                    Nama kelurahan harus yang sudah terdaftar di menu Wilayah. Bila nama kelurahan sama di beberapa
                    kecamatan, isi juga kolom <strong>Kecamatan</strong>. Kolom status boleh diisi
                    "Subsidi", "Komersil", atau "Campuran" (huruf besar/kecil bebas) — bila dikosongkan
                    dianggap "Komersil".
                </p>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="POST" action="{{ route('perumahan.import.simpan') }}"
                  enctype="multipart/form-data" data-unsaved-guard="File impor sudah dipilih tapi belum diproses.">
                @csrf

                <div class="mb-4">
                    <label for="file" class="mb-1 block text-sm font-semibold text-slate-700">File data perumahan</label>
                    <input id="file" type="file" name="file" accept=".csv,.xls,.xlsx"
                           class="w-full cursor-pointer rounded-lg border border-slate-300 px-3 py-2 text-sm file:mr-3 file:rounded-md file:border-0 file:bg-indigo-50 file:px-3 file:py-1.5 file:text-indigo-700 transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/20">
                    <p class="mt-1 text-xs text-slate-500">Baris pertama pada file wajib berisi judul kolom; pertahankan nama judul persis seperti template.</p>
                    <p class="mt-1 text-xs text-slate-500">Bagian PSU dan tipe unit belum termasuk — isi lewat halaman edit perumahan setelah datanya tersimpan.</p>
                </div>

                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-indigo-700">
                    Mulai Impor
                </button>
            </form>
        </div>
    </div>
@endsection
