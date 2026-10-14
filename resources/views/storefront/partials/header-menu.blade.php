@php
    $menuItems = collect($mainMenu->items ?? [])->where('is_active', true)->values();
    $primaryLimit = 4;
    $primaryItems = $menuItems->take($primaryLimit);
    $overflowItems = $menuItems->slice($primaryLimit)->values();
@endphp

<ul class="ig-menu">
    <li class="@if(request()->routeIs('home')) active @endif">
        <a href="{{ route('home') }}">{{ __('messages.home') }}</a>
    </li>

    @foreach($primaryItems as $item)
        @include('storefront.partials.header-menu-item', ['item' => $item])
    @endforeach

    @if($overflowItems->isNotEmpty())
        <li class="ig-menu-more">
            <button type="button" class="ig-menu-more-btn" aria-haspopup="true" aria-expanded="false" aria-label="More menu">
                <span class="ig-menu-more-dots" aria-hidden="true">⋯</span>
            </button>
            <ul class="ig-menu-more-panel">
                @foreach($overflowItems as $item)
                    @include('storefront.partials.header-menu-item', ['item' => $item, 'inOverflow' => true])
                @endforeach
            </ul>
        </li>
    @endif
</ul>
