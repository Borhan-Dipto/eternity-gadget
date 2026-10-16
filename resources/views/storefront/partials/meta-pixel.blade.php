{{-- Meta Pixel — set the ID in Admin → Site Settings → "Meta Pixel ID". Empty = disabled. --}}
@php($metaPixelId = \App\Support\MetaPixel::id())
@if($metaPixelId)
    @php($metaPixelQueue = \App\Support\MetaPixel::pullQueue())
    <script>
        !function(f,b,e,v,n,t,s){if(f.fbq)return;n=f.fbq=function(){n.callMethod?
        n.callMethod.apply(n,arguments):n.queue.push(arguments)};if(!f._fbq)f._fbq=n;
        n.push=n;n.loaded=!0;n.version='2.0';n.queue=[];t=b.createElement(e);t.async=!0;
        t.src=v;s=b.getElementsByTagName(e)[0];s.parentNode.insertBefore(t,s)}(window,
        document,'script','https://connect.facebook.net/en_US/fbevents.js');

        fbq('init', @json($metaPixelId), @json((object) \App\Support\MetaPixel::userData()));
        fbq('track', 'PageView');

        // Helper used by storefront scripts: egTrack('AddToCart', {...}, 'event-id')
        window.egTrack = function (eventName, params, eventId) {
            try {
                if (eventId) {
                    fbq('track', eventName, params || {}, { eventID: eventId });
                } else {
                    fbq('track', eventName, params || {});
                }
            } catch (e) {}
        };

        @foreach($metaPixelQueue as $queued)
            window.egTrack(@json($queued['event']), @json((object) $queued['params']), @json($queued['event_id']));
        @endforeach
    </script>
    @stack('meta_pixel')
    <noscript>
        <img height="1" width="1" style="display:none" alt=""
             src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1">
    </noscript>
@else
    <script>window.egTrack = function () {};</script>
@endif
