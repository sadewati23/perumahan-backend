@props([
    'tone' => 'neutral',
    'size' => 'md',
])

@php
    $tones = [
        'primary' => 'bg-brand-50 text-brand-700 ring-brand-600/20',
        'success' => 'bg-success-50 text-success-700 ring-success-600/20',
        'warning' => 'bg-warning-50 text-warning-800 ring-warning-600/20',
        'danger' => 'bg-danger-50 text-danger-700 ring-danger-600/20',
        'info' => 'bg-info-50 text-info-700 ring-info-600/20',
        'neutral' => 'bg-slate-100 text-slate-700 ring-slate-500/20',
    ];

    $sizes = [
        'sm' => 'px-2 py-0.5 text-[11px]',
        'md' => 'px-2.5 py-0.5 text-xs',
    ];

    $classes = 'inline-flex items-center gap-1 rounded-pill font-medium ring-1 ring-inset '
        .($tones[$tone] ?? $tones['neutral']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</span>
