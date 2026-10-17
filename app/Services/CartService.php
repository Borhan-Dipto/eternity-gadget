<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductOption;
use App\Support\ProductOptionGroups;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CartService
{
    private const SESSION_CART = 'cart';

    private const SESSION_SAVED = 'cart_saved';

    public function getItems(bool $includeSaved = false): Collection
    {
        if (Auth::check()) {
            $this->hydrateSessionFromDb();
        }

        $cart = $this->normalizeCart(session(self::SESSION_CART, []));

        return collect($cart)
            ->map(fn ($quantity, $cartKey) => $this->resolveCartItem((string) $cartKey, (int) $quantity))
            ->filter()
            ->values()
            ->when(! $includeSaved, fn (Collection $items) => $items->where('saved_for_later', false)->values())
            ->when($includeSaved, function (Collection $items) {
                $saved = $this->getSavedItems();

                return $items->merge($saved)->values();
            });
    }

    public function add(Product $product, int $quantity = 1, mixed $optionIds = null, bool $checkout = false): array
    {
        abort_unless($product->is_active, 404);

        $options = $this->resolveSelectedOptions($product, $optionIds);
        if ($options === false) {
            throw ValidationException::withMessages(['option_id' => 'অনুগ্রহ করে প্রোডাক্টের সব অপশন (যেমন মডেল/রং) বেছে নিন।']);
        }

        $primaryOption = $product->pricingOptionForSelection($options);
        $quantity = max(1, $quantity);
        $cartKey = $this->cartKey($product->id, $options->pluck('id')->all());
        $cart = $this->normalizeCart(session(self::SESSION_CART, []));
        $newQuantity = ($cart[$cartKey] ?? 0) + $quantity;

        if ($newQuantity > $product->availableStock($primaryOption)) {
            throw ValidationException::withMessages(['quantity' => 'এই পরিমাণ স্টকে নেই।']);
        }

        $cart[$cartKey] = $newQuantity;
        session([self::SESSION_CART => $cart]);
        $this->persistSessionToDb();

        return [
            'cart_key' => $cartKey,
            'quantity' => $newQuantity,
            'redirect_checkout' => $checkout,
        ];
    }

    public function update(array $quantities): void
    {
        $cart = $this->normalizeCart(session(self::SESSION_CART, []));

        foreach ($quantities as $cartKey => $quantity) {
            $quantity = (int) $quantity;

            if ($quantity < 1) {
                unset($cart[$cartKey]);
                continue;
            }

            $item = $this->resolveCartItem((string) $cartKey, $quantity);
            if (! $item) {
                unset($cart[$cartKey]);
                continue;
            }

            if ($quantity > $item['product']->availableStock($item['option'])) {
                throw ValidationException::withMessages([
                    'quantity' => 'Not enough stock for ' . $item['product']->name . '.',
                ]);
            }

            $cart[$cartKey] = $quantity;
        }

        session([self::SESSION_CART => $cart]);
        $this->persistSessionToDb();
    }

    public function remove(string $cartKey): void
    {
        $cart = $this->normalizeCart(session(self::SESSION_CART, []));
        unset($cart[$cartKey]);
        session([self::SESSION_CART => $cart]);

        if (Auth::check()) {
            [$productId] = $this->parseCartKey($cartKey);
            $optionIds = $this->parseCartOptionIds($cartKey);
            CartItem::query()
                ->where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->where('option_signature', $this->optionSignature($optionIds))
                ->where('saved_for_later', false)
                ->delete();
        }
    }

    public function mergeGuestToUser(int $userId): void
    {
        $sessionId = session()->getId();
        $sessionCart = $this->normalizeCart(session(self::SESSION_CART, []));
        $sessionSaved = $this->normalizeCart(session(self::SESSION_SAVED, []));

        foreach ($sessionCart as $cartKey => $quantity) {
            [$productId] = $this->parseCartKey($cartKey);
            $optionIds = $this->parseCartOptionIds($cartKey);
            $this->mergeDbCartRow($userId, $productId, $optionIds, (int) $quantity, false);
        }

        foreach ($sessionSaved as $cartKey => $quantity) {
            [$productId] = $this->parseCartKey($cartKey);
            $optionIds = $this->parseCartOptionIds($cartKey);
            $this->mergeDbCartRow($userId, $productId, $optionIds, (int) $quantity, true);
        }

        CartItem::query()->where('session_id', $sessionId)->each(function (CartItem $item) use ($userId) {
            $this->mergeDbCartRow(
                $userId,
                $item->product_id,
                $this->storedOptionIds($item),
                $item->quantity,
                $item->saved_for_later
            );
            $item->delete();
        });

        $this->hydrateSessionFromDb($userId);
    }

    public function saveForLater(string $cartKey): void
    {
        $cart = $this->normalizeCart(session(self::SESSION_CART, []));
        $quantity = (int) ($cart[$cartKey] ?? 0);

        if ($quantity < 1) {
            return;
        }

        unset($cart[$cartKey]);
        session([self::SESSION_CART => $cart]);

        $saved = $this->normalizeCart(session(self::SESSION_SAVED, []));
        $saved[$cartKey] = ($saved[$cartKey] ?? 0) + $quantity;
        session([self::SESSION_SAVED => $saved]);

        if (Auth::check()) {
            [$productId] = $this->parseCartKey($cartKey);
            $optionIds = $this->parseCartOptionIds($cartKey);
            CartItem::query()
                ->where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->where('option_signature', $this->optionSignature($optionIds))
                ->delete();

            $this->mergeDbCartRow(Auth::id(), $productId, $optionIds, $saved[$cartKey], true);
        }
    }

    public function moveToCart(string $cartKey): void
    {
        $saved = $this->normalizeCart(session(self::SESSION_SAVED, []));
        $quantity = (int) ($saved[$cartKey] ?? 0);

        if ($quantity < 1) {
            return;
        }

        unset($saved[$cartKey]);
        session([self::SESSION_SAVED => $saved]);

        $cart = $this->normalizeCart(session(self::SESSION_CART, []));
        $cart[$cartKey] = ($cart[$cartKey] ?? 0) + $quantity;
        session([self::SESSION_CART => $cart]);

        if (Auth::check()) {
            [$productId] = $this->parseCartKey($cartKey);
            $optionIds = $this->parseCartOptionIds($cartKey);
            CartItem::query()
                ->where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->where('option_signature', $this->optionSignature($optionIds))
                ->delete();

            $this->mergeDbCartRow(Auth::id(), $productId, $optionIds, $cart[$cartKey], false);
        }
    }

    public function persistSessionToDb(?int $userId = null): void
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return;
        }

        CartItem::query()
            ->where('user_id', $userId)
            ->where('saved_for_later', false)
            ->delete();

        $cart = $this->normalizeCart(session(self::SESSION_CART, []));

        foreach ($cart as $cartKey => $quantity) {
            [$productId] = $this->parseCartKey($cartKey);
            $optionIds = $this->parseCartOptionIds($cartKey);
            CartItem::create([
                'user_id' => $userId,
                'session_id' => null,
                'product_id' => $productId,
                'product_option_id' => $optionIds[0] ?? null,
                'option_signature' => $this->optionSignature($optionIds),
                'quantity' => (int) $quantity,
                'saved_for_later' => false,
            ]);
        }

        CartItem::query()
            ->where('user_id', $userId)
            ->where('saved_for_later', true)
            ->delete();

        $saved = $this->normalizeCart(session(self::SESSION_SAVED, []));

        foreach ($saved as $cartKey => $quantity) {
            [$productId] = $this->parseCartKey($cartKey);
            $optionIds = $this->parseCartOptionIds($cartKey);
            CartItem::create([
                'user_id' => $userId,
                'session_id' => null,
                'product_id' => $productId,
                'product_option_id' => $optionIds[0] ?? null,
                'option_signature' => $this->optionSignature($optionIds),
                'quantity' => (int) $quantity,
                'saved_for_later' => true,
            ]);
        }
    }

    public function getMiniCartData(): array
    {
        $items = $this->getItems();
        $preview = $items->take(3)->map(fn (array $item) => [
            'cart_key' => $item['cart_key'],
            'name' => $item['product']->name,
            'option_summary' => $item['option_summary'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'line_total' => $item['line_total'],
            'image' => media_url($item['image_path'] ?? $item['product']->primaryImage()),
        ])->values();

        return [
            'count' => (int) $items->sum('quantity'),
            'subtotal' => (float) $items->sum('line_total'),
            'items' => $preview,
            'has_more' => $items->count() > 3,
        ];
    }

    public function clear(): void
    {
        session()->forget([self::SESSION_CART, self::SESSION_SAVED]);

        if (Auth::check()) {
            CartItem::query()->where('user_id', Auth::id())->delete();
        }
    }

    public function cartKey(int $productId, mixed $optionIds = null): string
    {
        $ids = $this->normalizeOptionIds($optionIds);

        return $productId . '|' . ($ids === [] ? '' : implode('-', $ids));
    }

    private function getSavedItems(): Collection
    {
        if (Auth::check()) {
            return CartItem::query()
                ->where('user_id', Auth::id())
                ->where('saved_for_later', true)
                ->get()
                ->map(function (CartItem $row) {
                    $cartKey = $this->cartKey($row->product_id, $this->storedOptionIds($row));
                    $item = $this->resolveCartItem($cartKey, $row->quantity);

                    return $item ? [...$item, 'saved_for_later' => true] : null;
                })
                ->filter()
                ->values();
        }

        $saved = $this->normalizeCart(session(self::SESSION_SAVED, []));

        return collect($saved)
            ->map(fn ($quantity, $cartKey) => $this->resolveCartItem((string) $cartKey, (int) $quantity))
            ->filter()
            ->map(fn (array $item) => [...$item, 'saved_for_later' => true])
            ->values();
    }

    private function hydrateSessionFromDb(?int $userId = null): void
    {
        $userId ??= Auth::id();

        if (! $userId) {
            return;
        }

        if (session()->has(self::SESSION_CART) || session()->has(self::SESSION_SAVED)) {
            return;
        }

        $rows = CartItem::query()->where('user_id', $userId)->get();

        if ($rows->isEmpty()) {
            return;
        }

        $cart = [];
        $saved = [];

        foreach ($rows as $row) {
            $key = $this->cartKey($row->product_id, $this->storedOptionIds($row));
            if ($row->saved_for_later) {
                $saved[$key] = $row->quantity;
            } else {
                $cart[$key] = $row->quantity;
            }
        }

        session([
            self::SESSION_CART => $cart,
            self::SESSION_SAVED => $saved,
        ]);
    }

    private function mergeDbCartRow(int $userId, int $productId, array $optionIds, int $quantity, bool $savedForLater): void
    {
        $signature = $this->optionSignature($optionIds);
        $existing = CartItem::query()
            ->where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('option_signature', $signature)
            ->where('saved_for_later', $savedForLater)
            ->first();

        if ($existing) {
            $existing->update(['quantity' => $existing->quantity + $quantity]);

            return;
        }

        CartItem::create([
            'user_id' => $userId,
            'session_id' => null,
            'product_id' => $productId,
            'product_option_id' => $optionIds[0] ?? null,
            'option_signature' => $signature,
            'quantity' => $quantity,
            'saved_for_later' => $savedForLater,
        ]);
    }

    private function normalizeCart(array $cart): array
    {
        $normalized = [];

        foreach ($cart as $key => $value) {
            if (is_array($value)) {
                $normalized[$this->cartKey((int) $value['product_id'], $value['option_id'] ?? null)] = (int) $value['quantity'];
                continue;
            }

            if (str_contains((string) $key, '|')) {
                $normalized[(string) $key] = (int) $value;
                continue;
            }

            $normalized[$this->cartKey((int) $key, null)] = (int) $value;
        }

        return $normalized;
    }

    private function parseCartKey(string $cartKey): array
    {
        [$productId, $optionPart] = array_pad(explode('|', $cartKey, 2), 2, '');

        if ($optionPart === '') {
            return [(int) $productId, null];
        }

        if (str_contains($optionPart, '-')) {
            return [(int) $productId, (int) explode('-', $optionPart, 2)[0]];
        }

        return [(int) $productId, (int) $optionPart];
    }

    private function parseCartOptionIds(string $cartKey): array
    {
        [, $optionPart] = array_pad(explode('|', $cartKey, 2), 2, '');

        if ($optionPart === '') {
            return [];
        }

        return array_map('intval', explode('-', $optionPart));
    }

    /** @return \Illuminate\Support\Collection<int, ProductOption>|false */
    private function resolveSelectedOptions(Product $product, mixed $optionIds): Collection|false
    {
        $groups = ProductOptionGroups::validationGroups($product->options);

        if ($groups->isEmpty()) {
            return collect();
        }

        $ids = $this->normalizeOptionIds($optionIds);

        if ($ids === []) {
            return false;
        }

        $options = $product->options->whereIn('id', $ids)->values();

        if ($options->count() !== count($ids)) {
            return false;
        }

        foreach ($groups as $groupOptions) {
            if ($options->first(fn (ProductOption $option) => $groupOptions->contains('id', $option->id)) === null) {
                return false;
            }
        }

        return $options;
    }

    private function normalizeOptionIds(mixed $optionIds): array
    {
        if ($optionIds === null || $optionIds === '') {
            return [];
        }

        if (is_numeric($optionIds)) {
            return [(int) $optionIds];
        }

        if (is_array($optionIds)) {
            return collect($optionIds)
                ->flatten()
                ->filter(fn ($id) => $id !== null && $id !== '')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();
        }

        return [];
    }

    private function resolveCartItem(string $cartKey, int $quantity): ?array
    {
        [$productId] = $this->parseCartKey($cartKey);
        $optionIds = $this->parseCartOptionIds($cartKey);
        $product = Product::query()->whereKey($productId)->where('is_active', true)->with('options')->first();

        if (! $product) {
            return null;
        }

        $selectedOptions = collect($optionIds)
            ->map(fn (int $id) => $product->options->firstWhere('id', $id))
            ->filter()
            ->values();

        if ($optionIds !== [] && $selectedOptions->count() !== count($optionIds)) {
            return null;
        }

        $primaryOption = $product->pricingOptionForSelection($selectedOptions);
        $unit = $product->displayPrice($primaryOption);

        return [
            'cart_key' => $cartKey,
            'product' => $product,
            'option' => $primaryOption,
            'option_ids' => $selectedOptions->pluck('id')->all(),
            'image_path' => $product->imageForOptions($selectedOptions->pluck('id')),
            'option_summary' => ProductOptionGroups::formatSummary($selectedOptions),
            'quantity' => $quantity,
            'unit_price' => $unit,
            'line_total' => $unit * $quantity,
            'saved_for_later' => false,
        ];
    }

    private function optionSignature(array $optionIds): ?string
    {
        return $optionIds === [] ? null : implode('-', $this->normalizeOptionIds($optionIds));
    }

    private function storedOptionIds(CartItem $item): array
    {
        if ($item->option_signature) {
            return $this->normalizeOptionIds(explode('-', $item->option_signature));
        }

        return $item->product_option_id ? [(int) $item->product_option_id] : [];
    }
}
