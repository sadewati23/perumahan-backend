@props([
    'padded' => true,
])

<div {{ $attributes->merge(['class' => 'rounded-card border border-slate-200 bg-white shadow-card']) }}>
    @if ($padded)
        <div class="p-4 sm:p-5 lg:p-6">
            {{ $slot }}
        </div>
    @else
        {{ $slot }}
    @endif
</div>
