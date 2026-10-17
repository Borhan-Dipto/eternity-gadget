{{-- Tracking: Meta Pixel + Google Analytics 4. IDs live in Admin → Settings → Shop Settings → Tracking. Empty = off. --}}
@php
    $metaPixelId = \App\Support\MetaPixel::id();
    $ga4Id = trim((string) setting('ga4_measurement_id', ''));
    $ga4Id = preg_match('/^G-[A-Z0-9]{4,15}$/', $ga4Id) ? $ga4Id : null;
    $metaPixelQueue = $metaPixelId ? \App\Support\MetaPixel::pullQueue() : [];
@endphp
@if($ga4Id)
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $ga4Id }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', @json($ga4Id));
    </script>
@endif
<script>
    @if($metaPixelId)
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');

        fbq('init', @json($metaPixelId), @json((object) \App\Support\MetaPixel::userData()));
        fbq('track', 'PageView');
    @endif

    // One helper for every shop event: egTrack('AddToCart', {...}, 'event-id') → Meta Pixel and GA4.
    window.egTrack = function (eventName, params, eventId) {
        params = params || {};
        try {
            if (window.fbq) {
                eventId ? fbq('track', eventName, params, { eventID: eventId }) : fbq('track', eventName, params);
            }
        } catch (e) {}
        try {
            if (window.gtag) {
                var map = { ViewContent: 'view_item', AddToCart: 'add_to_cart', InitiateCheckout: 'begin_checkout', Purchase: 'purchase' };
                var name = map[eventName];
                if (!name) return;
                var items = (params.contents || []).map(function (c) {
                    return { item_id: String(c.id), quantity: c.quantity || 1, price: c.item_price };
                });
                var ga = { currency: params.currency || 'BDT', value: params.value || 0, items: items };
                if (name === 'purchase') ga.transaction_id = params.order_id || eventId;
                gtag('event', name, ga);
            }
        } catch (e) {}
    };

    @foreach($metaPixelQueue as $queued)
        window.egTrack(@json($queued['event']), @json((object) $queued['params']), @json($queued['event_id']));
    @endforeach
</script>
@stack('meta_pixel')
@if($metaPixelId)
    <noscript>
        <img height="1" width="1" style="display:none" alt=""
             src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1">
    </noscript>
@endif
