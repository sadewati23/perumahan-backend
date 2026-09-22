@extends('layouts.app')

@section('title', 'Tambah Perumahan')
@section('subtitle', 'Input data perumahan baru beserta PSU dan tipe unit')

@push('head')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
@endpush

@section('content')
    <form method="POST" action="{{ route('perumahan.store') }}">
        @csrf

        @include('perumahan._form', ['submitLabel' => 'Simpan Perumahan'])
    </form>
@endsection

@push('scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    @include('perumahan._form-scripts')
@endpush
