@props([
    'title' => '',
    'breadcrumbs' => [],
])

<header class="mb-6" {{ $attributes }}>
    @if (! empty($breadcrumbs))
        <nav aria-label="Breadcrumb">
            <ol class="flex flex-wrap items-center gap-1.5 text-sm text-slate-500">
                @foreach ($breadcrumbs as $crumb)
                    @php $lastCrumb = $loop->last; @endphp
                    <li class="flex items-center gap-1.5">
                        @if (! empty($crumb['url']) && ! $lastCrumb)
                            <a href="{{ $crumb['url'] }}" class="font-medium hover:text-brand-700 hover:underline">{{ $crumb['label'] }}</a>
                        @else
                            <span class="{{ $lastCrumb ? 'font-medium text-slate-700' : '' }}" @if ($lastCrumb) aria-current="page" @endif>{{ $crumb['label'] }}</span>
                        @endif
                        @if (! $lastCrumb)
                            <svg class="h-3.5 w-3.5 shrink-0 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        @endif
                    </li>
                @endforeach
            </ol>
        </nav>
    @endif

    <div class="mt-1 flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-xl font-semibold text-slate-900 sm:text-2xl">
            {{ $title }}
        </h1>
        @isset($actions)
            <div class="flex flex-wrap items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
    @isset($subtitle)
        <p class="mt-1 text-sm text-slate-600">{{ $subtitle }}</p>
    @endisset
</header>
