@props([
    'title' => '',
    'description' => '',
    'iconPath' => 'M4 13V7a2 2 0 012-2h12a2 2 0 012 2v6m0 0a2 2 0 012 2v3a2 2 0 01-2 2H4a2 2 0 01-2-2v-3a2 2 0 012-2m16 0H9a3 3 0 00-5 0H4',
    'icon' => 'inbox',
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-card border border-dashed border-slate-300 bg-white px-6 py-12 text-center']) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-pill bg-brand-50 text-brand-600">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $iconPath }}"/>
        </svg>
    </span>
    <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 max-w-sm text-sm text-slate-600">{{ $description }}</p>
    @endif
    @if (isset($actions) && trim($actions))
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2">
            {{ $actions }}
        </div>
    @endif
</div>
