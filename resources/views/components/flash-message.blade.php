@props([
    'tone' => 'success',
    'dismissible' => true,
    'autoDismiss' => false,
])

@php
    $tones = [
        'success' => [
            'border' => 'border-success-200 bg-success-50',
            'text' => 'text-success-800',
            'iconText' => 'text-success-600',
            'closeHover' => 'hover:bg-success-100 hover:text-success-800',
            'icon' => 'M5 13l4 4L19 7',
            'role' => 'status',
        ],
        'error' => [
            'border' => 'border-danger-200 bg-danger-50',
            'text' => 'text-danger-800',
            'iconText' => 'text-danger-600',
            'closeHover' => 'hover:bg-danger-100 hover:text-danger-800',
            'icon' => 'M12 9v4m0 4h.01M10.3 3.9L2.5 17a1.7 1.7 0 001.5 2.5h16a1.7 1.7 0 001.5-2.5L13.7 3.9a1.7 1.7 0 00-3 0z',
            'role' => 'alert',
        ],
        'info' => [
            'border' => 'border-info-200 bg-info-50',
            'text' => 'text-info-800',
            'iconText' => 'text-info-600',
            'closeHover' => 'hover:bg-info-100 hover:text-info-800',
            'icon' => 'M11 5h2m-2 4h.01M13 3H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V7l-4-4M9 8h6',
            'role' => 'status',
        ],
    ];

    $t = $tones[$tone] ?? $tones['success'];
@endphp

<div {{ $attributes->merge([
    'data-flash' => $tone,
    'tabindex' => '-1',
    'class' => 'mb-4 flex items-start gap-3 rounded-lg border px-4 py-3 text-sm '.$t['border'].' '.$t['text'],
]) }}
    @if ($autoDismiss) data-flash-auto @endif>
    <svg class="mt-0.5 h-5 w-5 shrink-0 {{ $t['iconText'] }}" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="{{ $t['icon'] }}"/>
    </svg>
    <div class="min-w-0 flex-1 pt-0.5" role="{{ $t['role'] }}">
        {{ $slot }}
    </div>
    @if ($dismissible)
        <button type="button" data-flash-dismiss aria-label="Tutup pesan"
                class="-mr-2 -mt-1 shrink-0 rounded-md p-1.5 transition {{ $t['closeHover'] }}">
            <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
    @endif
</div>
