<style>
    .cart-sidebar-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 200;
        opacity: 0;
        visibility: hidden;
        transition: opacity .25s ease, visibility .25s ease;
    }

    .cart-sidebar-overlay.open { opacity: 1; visibility: visible; }

    .cart-sidebar {
        position: fixed;
        top: 0;
        right: 0;
        width: min(420px, 100vw);
        max-width: 100%;
        height: 100%;
        background: #fff;
        z-index: 210;
        display: flex;
        flex-direction: column;
        transform: translateX(100%);
        transition: transform .3s ease, box-shadow .3s ease;
        box-shadow: none;
        font-family: var(--font-sans);
    }

    .cs-free { margin: 10px 0 4px; }
    .cs-free-text { font-size: 13px; color: #1a5c32; margin-bottom: 6px; text-align: center; }
    .cs-free-track { height: 6px; border-radius: 99px; background: #e8efe9; overflow: hidden; }
    .cs-free-fill { height: 100%; width: 0; background: linear-gradient(90deg, #f5924c, #1a7f45); border-radius: 99px; transition: width .4s ease; }
    .cs-free.done .cs-free-fill { background: #1a7f45; }
    .cs-note { font-size: 12px; color: #888; text-align: center; margin: 6px 0 10px; }
    .cart-sidebar.open { transform: translateX(0); box-shadow: -4px 0 24px rgba(0, 0, 0, 0.12); }

    .cs-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 18px 20px;
        border-bottom: 1px solid #eee;
        flex-shrink: 0;
    }

    .cs-header h2 {
        margin: 0;
        font-size: 20px;
        font-weight: 700;
        color: #111;
    }

    .cs-close {
        width: 36px;
        height: 36px;
        border: 0;
        background: transparent;
        font-size: 28px;
        line-height: 1;
        color: #666;
        cursor: pointer;
        padding: 0;
    }

    .cs-close:hover { color: #111; }

    .cs-body {
        flex: 1 1 auto;
        overflow-y: auto;
        padding: 16px 20px;
    }

    .cs-empty {
        text-align: center;
        color: #666;
        padding: 40px 12px;
        font-size: 15px;
    }

    .cs-item {
        background: #f5f5f5;
        padding: 14px;
        margin-bottom: 14px;
        position: relative;
    }

    .cs-item-row {
        display: flex;
        gap: 12px;
        align-items: flex-start;
    }

    .cs-item-info { flex: 1; min-width: 0; padding-right: 8px; }

    .cs-item-name {
        font-size: 13px;
        font-weight: 600;
        color: #111;
        line-height: 1.4;
        margin-bottom: 6px;
    }

    .cs-item-name a { color: inherit; text-decoration: none; }

    .cs-item-name a:hover { color: #f5924c; }

    .cs-item-options {
        list-style: none;
        margin: 0 0 10px;
        padding: 0;
        font-size: 12px;
        color: #555;
        line-height: 1.45;
    }

    .cs-item-options li::before { content: '- '; }

    .cs-thumb-wrap { position: relative; flex-shrink: 0; }

    .cs-thumb {
        width: 72px;
        height: 72px;
        object-fit: cover;
        background: #fff;
        border: 1px solid #e8e8e8;
        display: block;
    }

    .cs-remove {
        position: absolute;
        top: -8px;
        right: -8px;
        width: 22px;
        height: 22px;
        border: 1px solid #ccc;
        border-radius: 50%;
        background: #fff;
        color: #666;
        font-size: 14px;
        line-height: 1;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .cs-item-footer {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-top: 12px;
        gap: 12px;
    }

    .cs-qty {
        display: inline-flex;
        align-items: stretch;
        border: 1px solid #ddd;
        background: #fff;
    }

    .cs-qty button {
        width: 32px;
        border: 0;
        background: #f0f0f0;
        color: #333;
        font-size: 16px;
        cursor: pointer;
        padding: 0;
    }

    .cs-qty button.cs-qty-plus { background: #111; color: #fff; }

    .cs-qty span {
        min-width: 36px;
        text-align: center;
        line-height: 32px;
        font-size: 14px;
        border-left: 1px solid #ddd;
        border-right: 1px solid #ddd;
    }

    .cs-item-prices {
        display: flex;
        gap: 14px;
        font-size: 14px;
        font-weight: 600;
        color: #111;
    }

    .cs-footer {
        flex-shrink: 0;
        border-top: 1px solid #eee;
        padding: 16px 20px 20px;
        background: #fff;
    }

    .cs-total-line {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        font-size: 15px;
        color: #333;
    }

    .cs-total-line.grand {
        font-size: 17px;
        font-weight: 700;
        color: #111;
        border-top: 1px solid #eee;
        margin-top: 4px;
        padding-top: 12px;
    }

    .cs-total-line.grand span:last-child { color: #c0392b; }

    .cs-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
        margin-top: 14px;
    }

    .cs-btn {
        display: block;
        width: 100%;
        text-align: center;
        padding: 14px 16px;
        font-size: 14px;
        font-weight: 700;
        text-decoration: none;
        border: 0;
        cursor: pointer;
        font-family: inherit;
    }

    .cs-btn-view {
        background: #fff;
        color: #111;
        border: 1px solid #111;
    }

    .cs-btn-view:hover { background: #f8f8f8; color: #111; }

    .cs-btn-checkout {
        background: var(--brand);
        color: #fff;
    }

    .cs-btn-checkout:hover { background: var(--brand-dark); color: #fff; }

    body.cart-sidebar-open { overflow: hidden; }

    @media (max-width: 767px) {
        .cart-sidebar { width: min(58vw, 420px); min-width: 280px; }
    }
</style>

<div class="cart-sidebar-overlay" id="cart-sidebar-overlay" aria-hidden="true"></div>

<aside class="cart-sidebar" id="cart-sidebar" aria-label="Shopping cart" aria-hidden="true">
    <div class="cs-header">
        <h2>আপনার কার্ট</h2>
        <button type="button" class="cs-close" id="cart-sidebar-close" aria-label="বন্ধ করুন">&times;</button>
    </div>

    <div class="cs-body" id="cart-sidebar-items">
        <p class="cs-empty">আপনার কার্ট খালি।</p>
    </div>

    <div class="cs-footer">
        <div class="cs-total-line" style="display:none;">
            <span>সাবটোটাল</span>
            <span id="cs-subtotal">৳0</span>
        </div>
        <div class="cs-total-line grand">
            <span>সাবটোটাল</span>
            <span id="cs-total">৳0</span>
        </div>
        @php $csFreeMin = (float) setting('free_delivery_min', '0'); @endphp
        @if($csFreeMin > 0)
            <div class="cs-free" id="cs-free" data-min="{{ $csFreeMin }}" hidden>
                <div class="cs-free-text" id="cs-free-text"></div>
                <div class="cs-free-track"><div class="cs-free-fill" id="cs-free-fill"></div></div>
            </div>
        @endif
        <p class="cs-note">ডেলিভারি চার্জ চেকআউটে এলাকা অনুযায়ী যোগ হবে।</p>
        <div class="cs-actions">
            <button type="button" class="cs-btn cs-btn-view" id="cart-sidebar-continue">আরও কেনাকাটা</button>
            <a href="{{ route('checkout') }}" class="cs-btn cs-btn-checkout" id="cart-sidebar-checkout">অর্ডার করুন</a>
        </div>
    </div>
</aside>

<script>
(function () {
    const overlay = document.getElementById('cart-sidebar-overlay');
    const sidebar = document.getElementById('cart-sidebar');
    const itemsEl = document.getElementById('cart-sidebar-items');
    const subtotalEl = document.getElementById('cs-subtotal');
    const totalEl = document.getElementById('cs-total');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    function escapeHtml(str) {
        return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/"/g, '&quot;');
    }

    function optionLines(summary) {
        if (!summary) return '';
        return summary.split(/,\s*/).filter(Boolean).map((line) =>
            `<li>${escapeHtml(line)}</li>`
        ).join('');
    }

    function renderItems(data) {
        if (!data.items || data.items.length === 0) {
            itemsEl.innerHTML = '<p class="cs-empty">আপনার কার্ট খালি।</p>';
        } else {
            itemsEl.innerHTML = data.items.map((item) => `
                <div class="cs-item" data-cart-key="${escapeHtml(item.cart_key)}">
                    <div class="cs-item-row">
                        <div class="cs-item-info">
                            <div class="cs-item-name"><a href="${escapeHtml(item.url)}">${escapeHtml(item.name)}</a></div>
                            ${item.option_summary ? `<ul class="cs-item-options">${optionLines(item.option_summary)}</ul>` : ''}
                        </div>
                        <div class="cs-thumb-wrap">
                            <img class="cs-thumb" src="${escapeHtml(item.image)}" alt="">
                            <button type="button" class="cs-remove" data-remove="${escapeHtml(item.cart_key)}" aria-label="সরান">&times;</button>
                        </div>
                    </div>
                    <div class="cs-item-footer">
                        <div class="cs-qty" data-key="${escapeHtml(item.cart_key)}">
                            <button type="button" class="cs-qty-minus" aria-label="Decrease">&minus;</button>
                            <span class="cs-qty-num">${item.quantity}</span>
                            <button type="button" class="cs-qty-plus" aria-label="Increase">+</button>
                        </div>
                        <div class="cs-item-prices">
                            <span>${escapeHtml(item.unit_price)}</span>
                            <span>${escapeHtml(item.line_total)}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }

        subtotalEl.textContent = data.subtotal || '৳0';
        totalEl.textContent = data.subtotal || '৳0';

        const free = document.getElementById('cs-free');
        if (free) {
            const min = parseFloat(free.dataset.min || '0');
            const sub = parseFloat(data.subtotal_raw || 0);
            const fmt = (n) => '৳' + Math.ceil(n).toLocaleString('en-BD');
            free.hidden = !(min > 0 && sub > 0);
            document.getElementById('cs-free-fill').style.width = Math.min(100, sub / min * 100) + '%';
            document.getElementById('cs-free-text').innerHTML = sub >= min
                ? '🎉 আপনার অর্ডারে <strong>ডেলিভারি চার্জ ফ্রি!</strong>'
                : 'আর <strong>' + fmt(min - sub) + '</strong> এর প্রোডাক্ট নিলে ডেলিভারি চার্জ ফ্রি';
            free.classList.toggle('done', sub >= min);
        }
    }

    async function refreshCartSidebar() {
        try {
            const res = await fetch('/api/mini-cart');
            const data = await res.json();
            renderItems(data);
            document.dispatchEvent(new CustomEvent('cart:updated', { detail: data }));
            return data;
        } catch (e) {
            return null;
        }
    }

    async function postCart(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(body),
        });
        if (!res.ok) throw new Error('Cart update failed');
        return res.json();
    }

    function openCartSidebar() {
        window.hideCartToast?.(); // never show the "added" popup on top of the open cart
        overlay?.classList.add('open');
        sidebar?.classList.add('open');
        document.body.classList.add('cart-sidebar-open');
        overlay?.setAttribute('aria-hidden', 'false');
        sidebar?.setAttribute('aria-hidden', 'false');
        refreshCartSidebar();
    }

    function closeCartSidebar() {
        overlay?.classList.remove('open');
        sidebar?.classList.remove('open');
        document.body.classList.remove('cart-sidebar-open');
        overlay?.setAttribute('aria-hidden', 'true');
        sidebar?.setAttribute('aria-hidden', 'true');
    }

    window.openCartSidebar = openCartSidebar;
    window.closeCartSidebar = closeCartSidebar;
    window.refreshCartSidebar = refreshCartSidebar;

    document.getElementById('cart-sidebar-close')?.addEventListener('click', closeCartSidebar);
    document.getElementById('cart-sidebar-continue')?.addEventListener('click', closeCartSidebar);
    overlay?.addEventListener('click', closeCartSidebar);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && sidebar?.classList.contains('open')) closeCartSidebar();
    });

    itemsEl?.addEventListener('click', async (e) => {
        const removeBtn = e.target.closest('[data-remove]');
        if (removeBtn) {
            const data = await postCart('/api/cart/remove', { cart_key: removeBtn.dataset.remove });
            renderItems(data);
            document.dispatchEvent(new CustomEvent('cart:updated', { detail: data }));
            return;
        }

        const qtyWrap = e.target.closest('.cs-qty');
        if (!qtyWrap) return;

        const key = qtyWrap.dataset.key;
        const numEl = qtyWrap.querySelector('.cs-qty-num');
        let qty = parseInt(numEl?.textContent || '1', 10);

        if (e.target.classList.contains('cs-qty-minus')) {
            if (qty <= 1) return;
            qty -= 1;
        } else if (e.target.classList.contains('cs-qty-plus')) {
            qty += 1;
        } else {
            return;
        }

        const data = await postCart('/api/cart/update', { quantities: { [key]: qty } });
        renderItems(data);
        document.dispatchEvent(new CustomEvent('cart:updated', { detail: data }));
    });

    document.querySelectorAll('[data-open-cart]').forEach((el) => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            openCartSidebar();
        });
    });

    @if(session('success') && stripos(session('success'), 'cart') !== false)
    document.addEventListener('DOMContentLoaded', () => openCartSidebar());
    @endif
})();
</script>
