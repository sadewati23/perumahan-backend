@extends('layouts.publik')

@section('title', 'Cek Status Laporan')
@section('description', 'Masukkan kode laporan untuk melihat status penanganannya.')

@push('head')
    <meta name="robots" content="noindex">
@endpush

@section('content')
    <section class="mx-auto max-w-xl px-4 py-10 lg:px-8">
        <nav class="flex flex-wrap items-center gap-2 text-xs text-slate-600" aria-label="Breadcrumb">
            <a href="{{ route('publik.beranda') }}" class="hover:text-brand-700 hover:underline">Beranda</a>
            <span aria-hidden="true">/</span>
            <span class="text-slate-700" aria-current="page">Cek Status Laporan</span>
        </nav>

        <div class="mt-4 rounded-modal border border-garis bg-white p-6 shadow-card sm:p-8">
            <h1 class="font-judul text-3xl font-semibold tracking-tight text-tinta">Cek status laporan</h1>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                Masukkan kode laporan yang Anda terima saat mengirim laporan untuk melihat perkembangan penanganannya.
            </p>

            @if (config('laporan.hapus_otomatis'))
                <p class="mt-2 text-xs text-slate-600">
                    Laporan yang sudah selesai dihapus otomatis {{ config('laporan.hapus_otomatis_hari') }} hari setelah ditandai selesai.
                </p>
            @endif

            @if ($errors->any())
                <div class="mt-4 rounded-lg border border-danger-200 bg-danger-50 p-3 text-sm text-danger-800" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('cek-laporan.cari') }}" class="mt-6 space-y-4">
                @csrf
                <input type="text" name="jawaban_cadangan" value="" class="absolute -left-[9999px]" aria-hidden="true" tabindex="-1" autocomplete="off">

                <x-field name="kode" for="kode" label="Kode laporan" hint="Contoh: LPR-20261019-7K3MQ9XT">
                    <input id="kode" name="kode" value="{{ old('kode') }}" required autocomplete="off"
                           autocapitalize="characters" spellcheck="false" placeholder="LPR-20261019-7K3MQ9XT"
                           class="input font-mono tracking-wide">
                </x-field>

                @if (\App\Support\TurnstileKonfigurasi::tampilkanWidget())
                    <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div>
                @endif

                <x-button type="submit" class="w-full">Cek status</x-button>
            </form>

            <p class="mt-5 border-t border-garis pt-4 text-sm text-slate-600">
                Belum punya kode?
                <a class="font-semibold text-brand-700 hover:underline" href="{{ route('lapor.create') }}">Buat laporan</a>
            </p>
        </div>
    </section>
@endsection

@push('scripts')
    @if (\App\Support\TurnstileKonfigurasi::tampilkanWidget())
        <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
    @endif
@endpush
