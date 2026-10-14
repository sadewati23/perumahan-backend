@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'loading' => false,
    'href' => null,
])

@php
    $base = 'inline-flex min-h-11 items-center justify-center gap-2 rounded-control font-semibold transition md:min-h-10';

    $variants = [
        'primary' => 'bg-brand-600 text-white shadow-card hover:bg-brand-700 active:bg-brand-800',
        'secondary' => 'border border-slate-300 bg-white text-slate-700 shadow-card hover:bg-slate-50 active:bg-slate-100',
        'danger' => 'bg-danger-600 text-white shadow-card hover:bg-danger-700 active:bg-danger-800',
        'ghost' => 'text-slate-600 hover:bg-slate-100 hover:text-slate-900',
    ];

    $sizes = [
        'sm' => 'px-3 py-1.5 text-xs',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
    ];

    $classes = $base.' '.($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);

    $isDisabled = (bool) ($attributes->get('disabled') ?? false);
    $spinner = '<svg class="h-4 w-4 shrink-0 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if ($loading) aria-busy="true" @endif>
        @if ($loading) {!! $spinner !!} @endif
        <span class="{{ $loading ? 'opacity-80' : '' }}">{{ $slot }}</span>
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }} @if ($isDisabled || $loading) disabled @endif @if ($loading) aria-busy="true" @endif>
        @if ($loading) {!! $spinner !!} @endif
        <span class="{{ $loading ? 'opacity-80' : '' }}">{{ $slot }}</span>
    </button>
@endif
