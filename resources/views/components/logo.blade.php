@props([
    'ukuran' => 'h-10 w-10',
    'tile' => false,
])

@if ($tile)
    <span {{ $attributes->merge(['class' => 'flex shrink-0 items-center justify-center rounded-lg bg-white p-1 '.$ukuran]) }}>
        <img src="{{ asset('images/logo.png') }}" alt="" class="h-full w-full object-contain">
    </span>
@else
    <img src="{{ asset('images/logo.png') }}" alt="" {{ $attributes->merge(['class' => 'shrink-0 object-contain '.$ukuran]) }}>
@endif
