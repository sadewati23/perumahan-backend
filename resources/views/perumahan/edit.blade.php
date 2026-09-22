@extends('layouts.app')

@section('title', 'Ubah Data Perumahan')
@section('subtitle', $perumahan->nama)

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
    <form method="POST" action="{{ route('perumahan.update', $perumahan) }}">
        @csrf
        @method('PUT')

        @include('perumahan._form', [
            'submitLabel' => 'Perbarui Data',
            'cancelRoute' => route('perumahan.show', $perumahan),
        ])
    </form>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @include('perumahan._form-scripts')
@endpush
