@extends('layouts.app')

@section('title', 'Ubah Data Perumahan')
@section('subtitle', $perumahan->nama)

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="anonymous">
@endpush

@section('content')
    <form method="POST" action="{{ route('perumahan.update', $perumahan) }}"
          data-unsaved-guard="Ada perubahan yang belum disimpan.">
        @csrf
        @method('PUT')

        @include('perumahan._form', [
            'submitLabel' => 'Perbarui Data',
            'cancelRoute' => route('perumahan.show', $perumahan),
        ])
    </form>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin="anonymous"></script>
    @include('perumahan._form-scripts')
@endpush
