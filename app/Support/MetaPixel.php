<?php

namespace App\Support;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Meta (Facebook) Pixel helper.
 *
 * The Pixel ID is read from Admin → Site Settings → "meta_pixel_id".
 * When that setting is empty, nothing is rendered and no events fire.
 */
class MetaPixel
{
    public const CURRENCY = 'BDT';

    private const SESSION_QUEUE = 'meta_pixel_queue';

    private const SESSION_PURCHASED = 'meta_pixel_purchased_orders';

    public static function id(): ?string
    {
        $id = preg_replace('/\D/', '', (string) setting('meta_pixel_id', ''));

        return $id !== '' ? $id : null;
    }

    public static function enabled(): bool
    {
        return static::id() !== null;
    }

    public static function newEventId(string $prefix): string
    {
        return $prefix . '_' . Str::uuid()->toString();
    }

    /**
     * Queue an event to be fired on the next page that renders the storefront layout
     * (used when a normal form post redirects, e.g. the no-JavaScript add-to-cart fallback).
     */
    public static function queue(string $event, array $params = [], ?string $eventId = null): void
    {
        if (! static::enabled()) {
            return;
        }

        $queue = session(self::SESSION_QUEUE, []);
        $queue[] = [
            'event' => $event,
            'params' => $params,
            'event_id' => $eventId ?? static::newEventId(Str::snake($event)),
        ];
        session()->put(self::SESSION_QUEUE, $queue);
    }

    /** Return and clear the queued events. */
    public static function pullQueue(): array
    {
        return session()->pull(self::SESSION_QUEUE, []);
    }

    public static function productParams(Product $product, int $quantity = 1, ?float $unitPrice = null): array
    {
        $price = round($unitPrice ?? $product->effectivePrice(), 2);

        return [
            'content_ids' => [(string) $product->id],
            'content_name' => $product->name,
            'content_type' => 'product',
            'content_category' => $product->category?->name,
            'contents' => [['id' => (string) $product->id, 'quantity' => $quantity, 'item_price' => $price]],
            'value' => round($price * $quantity, 2),
            'currency' => self::CURRENCY,
        ];
    }

    /** @param Collection<int, array{product: Product, quantity: int, unit_price: float, line_total: float}> $cartItems */
    public static function cartParams(Collection $cartItems, ?float $value = null): array
    {
        $contents = $cartItems->map(fn (array $item) => [
            'id' => (string) $item['product']->id,
            'quantity' => (int) $item['quantity'],
            'item_price' => round((float) $item['unit_price'], 2),
        ])->values();

        return [
            'content_ids' => $contents->pluck('id')->unique()->values()->all(),
            'content_type' => 'product',
            'contents' => $contents->all(),
            'num_items' => (int) $cartItems->sum('quantity'),
            'value' => round($value ?? (float) $cartItems->sum('line_total'), 2),
            'currency' => self::CURRENCY,
        ];
    }

    public static function orderParams(Order $order): array
    {
        $contents = $order->items->map(fn ($item) => [
            'id' => (string) $item->product_id,
            'quantity' => (int) $item->quantity,
            'item_price' => round((float) $item->unit_price, 2),
        ])->values();

        return [
            'content_ids' => $contents->pluck('id')->unique()->values()->all(),
            'content_type' => 'product',
            'contents' => $contents->all(),
            'num_items' => (int) $order->items->sum('quantity'),
            'value' => round((float) $order->total, 2),
            'currency' => self::CURRENCY,
            'order_id' => (string) $order->order_number,
        ];
    }

    /** Same order → same event ID, so Meta can de-duplicate (and match a future Conversions API event). */
    public static function purchaseEventId(Order $order): string
    {
        return 'purchase_' . $order->order_number;
    }

    /** True only the first time this browser session sees the confirmation page for the order. */
    public static function shouldTrackPurchase(Order $order): bool
    {
        if (! static::enabled()) {
            return false;
        }

        $tracked = session(self::SESSION_PURCHASED, []);
        if (in_array($order->id, $tracked, true)) {
            return false;
        }

        $tracked[] = $order->id;
        session()->put(self::SESSION_PURCHASED, array_slice($tracked, -50));

        return true;
    }
}
