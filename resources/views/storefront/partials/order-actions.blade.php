@php
    $order->confirmIfExpired();
@endphp

@if($order->canCancel())
    <div class="order-cancel-box" data-cancel-until="{{ $order->cancelWindowEndsAt()->toIso8601String() }}">
        <p class="order-cancel-note">
            You can cancel this order within <strong class="order-cancel-timer">10:00</strong>.
            After that it is automatically confirmed for processing.
        </p>
        <form method="post" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?');">
            @csrf
            <button type="submit" class="order-btn order-btn-danger">Cancel Order</button>
        </form>
    </div>
@elseif($order->status === \App\Models\Order::STATUS_PENDING)
    <p class="order-status-note">This order is being confirmed for processing.</p>
@endif

@once
    @push('scripts')
    <script>
    (function () {
        document.querySelectorAll('[data-cancel-until]').forEach((box) => {
            const timerEl = box.querySelector('.order-cancel-timer');
            const endsAt = new Date(box.dataset.cancelUntil).getTime();
            if (!timerEl || Number.isNaN(endsAt)) return;

            const tick = () => {
                const remaining = Math.max(0, endsAt - Date.now());
                const totalSeconds = Math.floor(remaining / 1000);
                const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
                const seconds = String(totalSeconds % 60).padStart(2, '0');
                timerEl.textContent = minutes + ':' + seconds;

                if (remaining <= 0) {
                    box.querySelector('form')?.remove();
                    const note = box.querySelector('.order-cancel-note');
                    if (note) {
                        note.textContent = 'The cancellation window has ended. This order is now being processed.';
                    }
                    clearInterval(interval);
                }
            };

            tick();
            const interval = setInterval(tick, 1000);
        });
    })();
    </script>
    @endpush
@endonce
