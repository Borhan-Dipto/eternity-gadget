<style>
    .cart-toast {
        position: fixed;
        right: 16px;
        bottom: calc(72px + env(safe-area-inset-bottom, 0px));
        z-index: 220;
        width: min(320px, calc(100vw - 32px));
        background: #fff;
        border: 1px solid #e8e8e8;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.18);
        border-radius: 8px;
        padding: 14px;
        transform: translateY(20px) scale(0.96);
        opacity: 0;
        visibility: hidden;
        transition: transform .35s cubic-bezier(.34, 1.4, .64, 1), opacity .3s ease, visibility .3s ease;
        font-family: var(--font-sans);
    }

    .cart-toast.show {
        transform: translateY(0) scale(1);
        opacity: 1;
        visibility: visible;
    }

    .cart-toast-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
        margin-bottom: 12px;
    }

    .cart-toast-row img {
        width: 64px;
        height: 64px;
        object-fit: cover;
        border: 1px solid #eee;
        flex-shrink: 0;
    }

    .cart-toast-info { flex: 1; min-width: 0; }

    .cart-toast-name {
        font-size: 13px;
        font-weight: 600;
        color: #111;
        line-height: 1.35;
        margin-bottom: 4px;
    }

    .cart-toast-meta { font-size: 12px; color: #666; margin-bottom: 4px; }

    .cart-toast-price { font-size: 14px; font-weight: 700; color: #c0392b; }

    .cart-toast-actions {
        display: flex;
        gap: 8px;
    }

    .cart-toast-btn {
        flex: 1;
        text-align: center;
        padding: 10px 8px;
        font-size: 12px;
        font-weight: 700;
        text-decoration: none;
        border: 0;
        cursor: pointer;
        font-family: inherit;
    }

    .cart-toast-btn-view {
        background: #fff;
        color: #111;
        border: 1px solid #111;
    }

    .cart-toast-btn-checkout {
        background: var(--brand);
        color: #fff;
    }

    @media (min-width: 992px) {
        .cart-toast { bottom: 24px; }
    }
</style>

<div class="cart-toast" id="cart-toast" role="status" aria-live="polite" aria-hidden="true">
    <div class="cart-toast-row">
        <img id="cart-toast-img" src="" alt="">
        <div class="cart-toast-info">
            <div class="cart-toast-name" id="cart-toast-name"></div>
            <div class="cart-toast-meta" id="cart-toast-meta"></div>
            <div class="cart-toast-price" id="cart-toast-price"></div>
        </div>
    </div>
    <div class="cart-toast-actions">
        <button type="button" class="cart-toast-btn cart-toast-btn-view" id="cart-toast-view">কার্ট দেখুন</button>
        <a href="{{ route('checkout') }}" class="cart-toast-btn cart-toast-btn-checkout" id="cart-toast-checkout">অর্ডার করুন</a>
    </div>
</div>

<script>
(function () {
    const toast = document.getElementById('cart-toast');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    let hideTimer;

    function showCartToast(product, preferCheckout) {
        if (!toast || !product) return;
        document.getElementById('cart-toast-img').src = product.image || '';
        document.getElementById('cart-toast-name').textContent = product.name || '';
        document.getElementById('cart-toast-meta').textContent = product.option_summary || 'কার্টে যোগ হয়েছে ✓';
        document.getElementById('cart-toast-price').textContent = product.price || '';

        toast.classList.add('show');
        toast.setAttribute('aria-hidden', 'false');

        clearTimeout(hideTimer);
        hideTimer = setTimeout(hideCartToast, preferCheckout ? 6000 : 4500);
    }

    function hideCartToast() {
        toast?.classList.remove('show');
        toast?.setAttribute('aria-hidden', 'true');
    }

    window.showCartToast = showCartToast;
    window.hideCartToast = hideCartToast;

    document.getElementById('cart-toast-view')?.addEventListener('click', () => {
        hideCartToast();
        window.openCartSidebar?.();
    });

    document.addEventListener('submit', async (e) => {
        const form = e.target.closest('.js-cart-add-form');
        if (!form) return;
        e.preventDefault();

        const submitter = e.submitter;
        const preferCheckout = submitter?.name === 'checkout' || form.querySelector('[name="checkout"]')?.value === '1';
        const action = form.getAttribute('action');
        const match = action?.match(/\/cart\/add\/(\d+)/);
        if (!match) { form.submit(); return; }

        const productId = match[1];
        const formData = new FormData(form);
        if (submitter?.name) formData.append(submitter.name, submitter.value);

        try {
            const res = await fetch('/api/cart/add/' + productId, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: formData,
            });

            const data = await res.json();

            if (!res.ok || !data.success) {
                alert(data.message || 'কার্টে যোগ করা যায়নি। প্রোডাক্টের সব অপশন বেছে নিন।');
                return;
            }

            if (data.cart) {
                document.dispatchEvent(new CustomEvent('cart:updated', { detail: data.cart }));
            }

            if (data.pixel && window.egTrack) {
                window.egTrack('AddToCart', data.pixel.params, data.pixel.event_id);
            }

            showCartToast(data.product, preferCheckout);

            if (preferCheckout && data.redirect_checkout) {
                setTimeout(() => { window.location.href = '{{ route('checkout') }}'; }, 800);
            }
        } catch (err) {
            form.submit();
        }
    });
})();
</script>
