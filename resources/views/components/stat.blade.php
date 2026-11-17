@props([
    'label' => '',
    'value' => '',
    'satuan' => null,
    'meta' => null,
    'icon' => 'kotak',
    'tone' => 'brand',
    'href' => null,
])

@php
    $tones = [
        'brand' => 'bg-brand-50 text-brand-700 ring-brand-200',
        'info' => 'bg-info-50 text-info-700 ring-info-200',
        'success' => 'bg-success-50 text-success-700 ring-success-200',
        'warning' => 'bg-warning-50 text-warning-800 ring-warning-200',
        'danger' => 'bg-danger-50 text-danger-700 ring-danger-200',
        'neutral' => 'bg-kertas-tua text-tinta-lembut ring-garis',
    ];

    $kelas = 'block rounded-card border border-garis bg-white p-4 shadow-card transition sm:p-5 '
        .($href ? 'hover:border-brand-300 hover:shadow-card-hover' : '');
    $tag = $href ? 'a' : 'div';
@endphp

<{{ $tag }} @if ($href) href="{{ $href }}" @endif {{ $attributes->merge(['class' => $kelas]) }}>
    <div class="flex items-start justify-between gap-3">
        <p class="text-sm font-medium text-slate-600">{{ $label }}</p>
        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 ring-inset {{ $tones[$tone] ?? $tones['brand'] }}">
            <x-icon :name="$icon" class="h-4 w-4"/>
        </span>
    </div>

    <p class="angka-utama mt-2">{{ $value }}</p>

    @if ($satuan || isset($meta))
        <p class="mt-0.5 flex flex-wrap items-center gap-x-1 text-xs text-slate-600">
            @if ($satuan)<span>{{ $satuan }}</span>@endif
            @if ($satuan && isset($meta))<span aria-hidden="true">&middot;</span>@endif
            @isset($meta){{ $meta }}@endisset
        </p>
    @endif
</{{ $tag }}>
