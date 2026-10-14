@php
    $children = $item->children->where('is_active', true);
    $hasChildren = $children->isNotEmpty();
    $hasGrandchildren = $hasChildren && $children->contains(fn ($child) => $child->children->where('is_active', true)->isNotEmpty());
    $inOverflow = $inOverflow ?? false;
@endphp

<li class="@if($hasGrandchildren) has-mega @endif @if($hasChildren) has-children @endif">
    <a href="{{ $item->url ?: '#' }}">
        {{ $item->label }}
        @if($hasChildren)<span class="menu-chevron">{{ $inOverflow ? '›' : '▾' }}</span>@endif
    </a>

    @if($hasGrandchildren)
        <div class="ig-mega">
            @foreach($children as $child)
                @php $grandchildren = $child->children->where('is_active', true); @endphp
                <div class="ig-mega-col">
                    <a class="ig-mega-heading" href="{{ $child->url ?: '#' }}">{{ $child->label }}</a>
                    @if($grandchildren->isNotEmpty())
                        <ul>
                            @foreach($grandchildren as $grandchild)
                                <li><a href="{{ $grandchild->url ?: '#' }}">{{ $grandchild->label }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>
    @elseif($hasChildren)
        <ul class="ig-submenu">
            @foreach($children as $child)
                @php $grandchildren = $child->children->where('is_active', true); @endphp
                <li class="@if($grandchildren->isNotEmpty()) has-flyout @endif">
                    <a href="{{ $child->url ?: '#' }}">
                        {{ $child->label }}
                        @if($grandchildren->isNotEmpty())<span class="menu-chevron">›</span>@endif
                    </a>
                    @if($grandchildren->isNotEmpty())
                        <ul class="ig-flyout">
                            @foreach($grandchildren as $grandchild)
                                <li><a href="{{ $grandchild->url ?: '#' }}">{{ $grandchild->label }}</a></li>
                            @endforeach
                        </ul>
                    @endif
                </li>
            @endforeach
        </ul>
    @endif
</li>
