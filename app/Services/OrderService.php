<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly EmailService $emailService,
    ) {}

    // Create Order Code
    public function create(array $data): Order
    {
        $order = DB::transaction(function () use ($data) {
            $order = Order::create([
                'order_number' => $this->generateOrderNumber(),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_phone' => $data['customer_phone'] ?? null,
                'shipping_address' => $data['shipping_address'],
                'shipping_amount' => max(0, (float) ($data['shipping_amount'] ?? 0)),
                // Never trust caller-supplied status/payment state on create.
                'status' => OrderStatus::Pending,
                'payment_status' => PaymentStatus::Unpaid,
            ]);

            $this->syncItems($order, $data['items'] ?? []);

            return $order->refresh()->load('items');
        });

        if ($order->customer_email) {
            $this->sendOrderEmail($order);
        }

        return $order;
    }

    // Update Order Code
    public function update(Order $order, array $data): Order
    {
        $oldStatus = $order->status;

        $order = DB::transaction(function () use ($order, $data) {
            $order = Order::query()
                ->lockForUpdate()
                ->findOrFail($order->id);

            $newStatus = $data['status'] ?? $order->status;
            $newStatus = $newStatus instanceof OrderStatus ? $newStatus : OrderStatus::tryFrom((string) $newStatus);

            if ($newStatus === null) {
                throw ValidationException::withMessages(['status' => 'Invalid order status.']);
            }

            if ($newStatus !== $order->status && ! $order->status->canTransitionTo($newStatus)) {
                throw ValidationException::withMessages([
                    'status' => "Cannot transition from {$order->status->value} to {$newStatus->value}.",
                ]);
            }

            if ($order->status === OrderStatus::Cancelled && ! empty($data['items'])) {
                throw ValidationException::withMessages([
                    'items' => 'Cancelled orders cannot be edited.',
                ]);
            }

            $order->fill([
                'customer_name' => $data['customer_name'] ?? $order->customer_name,
                'customer_email' => $data['customer_email'] ?? $order->customer_email,
                'customer_phone' => $data['customer_phone'] ?? $order->customer_phone,
                'shipping_address' => $data['shipping_address'] ?? $order->shipping_address,
                'shipping_amount' => isset($data['shipping_amount'])
                    ? max(0, (float) $data['shipping_amount'])
                    : $order->shipping_amount,
                'status' => $newStatus,
                'payment_status' => $data['payment_status'] ?? $order->payment_status,
            ])->save();

            $items = $data['items'] ?? $order->items
                ->map(fn (OrderItem $item) => [
                    'id' => $item->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                ])
                ->all();

            $this->syncItems($order, $items);

            return $order->refresh()->load('items');
        });

        if ($order->customer_email && $order->status !== $oldStatus) {
            $this->sendStatusEmail($order);
        }

        return $order;
    }

    // previewSubtotal Order Code
    public static function previewSubtotal(?array $items): float
    {
        $items = collect($items ?? [])
            ->filter(fn ($item) => ! empty($item['product_id']));

        $prices = Product::withTrashed()
            ->whereIn('id', $items->pluck('product_id'))
            ->pluck('price', 'id');

        return (float) $items->sum(
            fn ($item) => ($prices[$item['product_id']] ?? 0)
                * (int) $item['quantity']
        );
    }

    private function syncItems(Order $order, array $lines): void
    {
        if (empty($lines)) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one product.',
            ]);
        }

        $productIds = collect($lines)->pluck('product_id')->map(fn ($id) => (int) $id);

        if ($productIds->contains(0) || $productIds->unique()->count() !== $productIds->count()) {
            throw ValidationException::withMessages([
                'items' => 'Select valid and unique products.',
            ]);
        }

        foreach ($lines as $line) {
            $quantity = $line['quantity'] ?? null;

            if (! is_numeric($quantity) || (int) $quantity < 1 || (float) $quantity !== (float) (int) $quantity) {
                throw ValidationException::withMessages([
                    'items' => 'Quantity must be a whole number greater than zero.',
                ]);
            }
        }

        $existing = $order->items()->lockForUpdate()->get()->keyBy('id');

        $products = Product::withTrashed()
            ->whereIn('id', $productIds->merge($existing->pluck('product_id'))->unique())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $stockChanges = [];
        $keepIds = [];
        $commitsStock = $order->status->commitsStock();

        foreach ($lines as $line) {
            $productId = (int) $line['product_id'];
            $quantity = (int) $line['quantity'];

            $product = $products->get($productId);
            $item = isset($line['id']) ? $existing->get($line['id']) : null;

            if (! $product || (isset($line['id']) && ! $item)) {
                throw ValidationException::withMessages([
                    'items' => 'Invalid product or order item.',
                ]);
            }

            $sameProduct = $item && $item->product_id === $productId;
            $base = $sameProduct ? $item : null;

            if (
                (! $base || $base->quantity !== $quantity) &&
                ($product->trashed() || ! $product->is_active)
            ) {
                throw ValidationException::withMessages([
                    'items' => "{$product->name} is not available.",
                ]);
            }

            $committed = $commitsStock ? $quantity : 0;
            $price = (float) ($base?->unit_price ?? $product->price);

            $stockChanges[$productId] = ($stockChanges[$productId] ?? 0)
                + $committed
                - ($base?->committed_quantity ?? 0);

            if ($base) {
                $keepIds[] = $base->id;
            }

            $attributes = [
                'product_id' => $productId,
                'product_name' => $base?->product_name ?? $product->name,
                'unit_price' => $price,
                'quantity' => $quantity,
                'line_subtotal' => round($price * $quantity, 2),
                'committed_quantity' => $committed,
            ];

            $base
                ? $base->update($attributes)
                : $order->items()->create($attributes);
        }

        $removed = $existing->reject(
            fn (OrderItem $item) => in_array($item->id, $keepIds, true)
        );

        foreach ($removed as $item) {
            $stockChanges[$item->product_id] = ($stockChanges[$item->product_id] ?? 0)
                - $item->committed_quantity;
        }

        foreach ($stockChanges as $productId => $change) {
            $product = $products->get($productId);

            if ($change > $product->stock_quantity) {
                throw ValidationException::withMessages([
                    'items' => "Insufficient stock for {$product->name}.",
                ]);
            }

            if ($change !== 0) {
                $product->decrement('stock_quantity', $change);
            }
        }

        $removed->each->delete();

        $subtotal = $order->items()->sum('line_subtotal');

        $order->update([
            'subtotal' => $subtotal,
            'total_amount' => $subtotal + $order->shipping_amount,
        ]);
    }

    // Send Order Email Code
    private function sendOrderEmail(Order $order): void
    {
        $this->emailService->queueTemplate(
            'order_confirmation',
            $order->customer_email,
            $this->emailPayload($order)
        );
    }

    private function sendStatusEmail(Order $order): void
    {
        $this->emailService->queueTemplate(
            'order_status_update',
            $order->customer_email,
            $this->emailPayload($order)
        );
    }

    /** @return array<string, mixed> */
    private function emailPayload(Order $order): array
    {
        return [
            'customer' => [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
                'phone' => $order->customer_phone,
                'address' => $order->shipping_address,
            ],
            'order' => [
                'number' => $order->order_number,
                'status' => $order->status->getLabel(),
                'payment_status' => $order->payment_status->getLabel(),
                'subtotal' => number_format($order->subtotal, 2),
                'shipping' => number_format($order->shipping_amount, 2),
                'total' => number_format($order->total_amount, 2),
                'items' => $order->items
                    ->map(fn (OrderItem $item) => "{$item->product_name} x {$item->quantity}")
                    ->implode(', '),
            ],
        ];
    }

    // Generate Unique Order No
    private function generateOrderNumber(): string
    {
        return 'ORD-'.Str::upper(Str::random(8));
    }
}
