@extends('layouts.storefront')

@section('title', setting('meta_title', setting('site_name', 'Eternity Gadget')))

@section('content')
<h1 style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0;">{{ setting('site_name', 'Eternity Gadget') }} — premium phone cases, chargers and gadgets in Bangladesh</h1>
    @if($sliders->isNotEmpty())
        <section class="hero-carousel" id="heroCarousel" aria-label="Featured banners">
            <div class="hero-carousel-track" id="heroCarouselTrack">
                @foreach($sliders as $index => $slider)
                    <div class="hero-carousel-slide">
                        <a href="{{ $slider->button_url ?: route('search') }}">
                            <img
                                src="{{ media_thumb($slider->image, 1000) }}"
                                srcset="{{ media_thumb($slider->image, 1000) }} 1000w, {{ media_url($slider->image) }} 1600w"
                                sizes="100vw"
                                decoding="async"
                                alt="{{ $slider->title }}"
                                @if($index === 0) fetchpriority="high" @endif
                                loading="{{ $index === 0 ? 'eager' : 'lazy' }}"
                            >
                        </a>
                    </div>
                @endforeach
            </div>

            @if($sliders->count() > 1)
                <button type="button" class="hero-carousel-btn prev" id="heroCarouselPrev" aria-label="Previous slide">&#8249;</button>
                <button type="button" class="hero-carousel-btn next" id="heroCarouselNext" aria-label="Next slide">&#8250;</button>
                <div class="hero-carousel-dots" id="heroCarouselDots">
                    @foreach($sliders as $index => $slider)
                        <button
                            type="button"
                            class="hero-carousel-dot @if($index === 0) active @endif"
                            data-index="{{ $index }}"
                            aria-label="Go to slide {{ $index + 1 }}"
                        ></button>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <div class="container">
        <div class="section-title"><h2>Shop By Categories</h2></div>
        <div class="category-grid">
            @foreach($featuredCategories as $category)
                <a class="category-card" href="{{ route('category.show', $category->slug) }}">
                    <img src="{{ media_thumb($category->image, 300) }}" alt="{{ $category->name }}" loading="lazy" decoding="async">
                    <h4>{{ $category->name }}</h4>
                </a>
            @endforeach
        </div>

        @foreach($sections as $section)
            <div class="section-title">
                <h2>{{ $section->title }}</h2>
                @if($section->view_more_url)<a href="{{ $section->view_more_url }}">সব দেখুন</a>@endif
            </div>
            @if($section->type === 'categories')
                <div class="category-grid">
                    @foreach($featuredCategories as $category)
                        <a class="category-card" href="{{ route('category.show', $category->slug) }}">
                            <img src="{{ media_thumb($category->image, 300) }}" alt="{{ $category->name }}" loading="lazy" decoding="async">
                            <h4>{{ $category->name }}</h4>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="product-scroll-row">
                    @foreach($section->products as $product)
                        @include('storefront.partials.product-card', ['product' => $product])
                    @endforeach
                </div>
            @endif
        @endforeach
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const carousel = document.getElementById('heroCarousel');
    const track = document.getElementById('heroCarouselTrack');
    if (!carousel || !track) return;

    const slides = track.querySelectorAll('.hero-carousel-slide');
    const dots = Array.from(document.querySelectorAll('#heroCarouselDots .hero-carousel-dot'));
    const total = slides.length;
    let index = 0;
    let autoplayTimer;
    let touchStartX = 0;
    let touchEndX = 0;

    function syncCarouselHeight() {
        const activeIndex = total <= 1 ? 0 : index;
        const img = slides[activeIndex]?.querySelector('img');
        if (!img) return;

        const apply = () => {
            const height = img.getBoundingClientRect().height;
            if (height > 0) {
                carousel.style.height = height + 'px';
            }
        };

        if (img.complete && img.naturalHeight > 0) {
            apply();
        } else {
            img.addEventListener('load', apply, { once: true });
        }
    }

    slides.forEach((slide) => {
        slide.querySelector('img')?.addEventListener('load', syncCarouselHeight);
    });

    window.addEventListener('resize', syncCarouselHeight);

    function goTo(i) {
        index = (i + total) % total;
        track.style.transform = 'translateX(-' + (index * 100) + '%)';
        dots.forEach((dot, dotIndex) => dot.classList.toggle('active', dotIndex === index));
        syncCarouselHeight();
    }

    syncCarouselHeight();

    if (total <= 1) return;

    function next() { goTo(index + 1); }
    function prev() { goTo(index - 1); }

    function startAutoplay() {
        stopAutoplay();
        autoplayTimer = setInterval(next, 5000);
    }

    function stopAutoplay() {
        if (autoplayTimer) clearInterval(autoplayTimer);
    }

    document.getElementById('heroCarouselNext')?.addEventListener('click', () => {
        next();
        startAutoplay();
    });

    document.getElementById('heroCarouselPrev')?.addEventListener('click', () => {
        prev();
        startAutoplay();
    });

    dots.forEach((dot) => {
        dot.addEventListener('click', () => {
            goTo(Number(dot.dataset.index));
            startAutoplay();
        });
    });

    carousel.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0].screenX;
        stopAutoplay();
    }, { passive: true });

    carousel.addEventListener('touchend', (e) => {
        touchEndX = e.changedTouches[0].screenX;
        const diff = touchStartX - touchEndX;
        if (Math.abs(diff) > 50) {
            diff > 0 ? next() : prev();
        }
        startAutoplay();
    }, { passive: true });

    carousel.addEventListener('mouseenter', stopAutoplay);
    carousel.addEventListener('mouseleave', startAutoplay);

    goTo(0);
    startAutoplay();
})();
</script>
@endpush
