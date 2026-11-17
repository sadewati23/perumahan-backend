@props([
    'padded' => true,
    'heading' => null,
    'meta' => null,
])

<div {{ $attributes->merge(['class' => 'rounded-card border border-garis bg-white shadow-card']) }}>
    @if ($heading || isset($actions))
        <div class="flex flex-wrap items-start justify-between gap-2 border-b border-garis px-4 py-3 sm:px-5">
            <div class="min-w-0">
                @if ($heading)
                    <h2 class="font-judul text-base font-semibold tracking-tight text-tinta">{{ $heading }}</h2>
                @endif
                @if ($meta)
                    <p class="mt-0.5 text-xs text-slate-600">{{ $meta }}</p>
                @endif
            </div>
            @isset($actions)
                <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
            @endisset
        </div>
    @endif

    @if ($padded)
        <div class="p-4 sm:p-5 lg:p-6">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
