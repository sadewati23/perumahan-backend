@props([
    'perumahan',
    'lazy' => true,
])

@if ($perumahan->foto_url)
    <div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-neutral-100']) }}>
        <img src="{{ $perumahan->foto_url }}" alt="" aria-hidden="true" decoding="async"
             @if ($lazy) loading="lazy" @endif
             class="pointer-events-none absolute inset-0 h-full w-full scale-110 object-cover opacity-60 blur-xl">
        <img src="{{ $perumahan->foto_url }}" alt="Foto {{ $perumahan->nama }}" width="640" height="360" decoding="async"
             @if ($lazy) loading="lazy" @endif
             class="relative h-full w-full object-contain">
    </div>
@else
    <div {{ $attributes->merge(['class' => 'relative overflow-hidden bg-neutral-100']) }}>
        <img src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='640' height='360' viewBox='0 0 640 360'%3E%3Crect width='640' height='360' fill='%23e2e8f0'/%3E%3Cpath d='M220 240l80-90 70 80h-150zm160 0l-50-55-40 45h-70l65-75 40 45 70-80 105 120h-120z' fill='%2394a3b8'/%3E%3Ctext x='320' y='200' font-family='Instrument Sans,Arial' font-size='20' fill='%2364748b' text-anchor='middle'%3EPerumahan%3C/text%3E%3C/svg%3E"
             alt="Ilustrasi perumahan" width="640" height="360" @if ($lazy) loading="lazy" @endif class="h-full w-full object-cover">
    </div>
@endif
