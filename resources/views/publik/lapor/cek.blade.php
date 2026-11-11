@extends('layouts.publik')
@section('title', 'Cek Status Laporan')
@push('head')<meta name="robots" content="noindex">@endpush
@section('content')
<section class="mx-auto max-w-xl px-4 py-10 lg:px-8">
    <x-card class="masuk-naik">
        <h1 class="text-2xl font-semibold text-slate-900">Cek Status Laporan</h1>
        @if (config('laporan.hapus_otomatis'))<p class="mt-2 text-sm text-slate-600">Laporan yang sudah selesai dihapus otomatis {{ config('laporan.hapus_otomatis_hari') }} hari setelah ditandai selesai.</p>@endif
        @if ($errors->any()) <div class="mt-4 rounded-lg border border-danger-200 bg-danger-50 p-3 text-sm text-danger-800">{{ $errors->first() }}</div> @endif
        <form method="POST" action="{{ route('cek-laporan.cari') }}" class="mt-6 space-y-4">
            @csrf
            <input type="text" name="jawaban_cadangan" value="" class="absolute -left-[9999px]" aria-hidden="true" tabindex="-1" autocomplete="off">
            <x-field name="kode" for="kode" label="Kode Laporan">
                <input id="kode" name="kode" value="{{ old('kode') }}" required autocomplete="off" autocapitalize="characters" spellcheck="false" placeholder="LPR-20261019-7K3MQ9XT" class="input">
            </x-field>
            @if (\App\Support\TurnstileKonfigurasi::tampilkanWidget()) <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}"></div> @endif
            <x-button type="submit">Cek Status</x-button>
        </form>
        <p class="mt-5 text-sm text-slate-600">Belum punya kode? <a class="font-semibold text-brand-600 hover:underline" href="{{ route('lapor.create') }}">Buat laporan</a></p>
    </x-card>
</section>
@endsection
@push('scripts')
@if (\App\Support\TurnstileKonfigurasi::tampilkanWidget())<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>@endif
@endpush
