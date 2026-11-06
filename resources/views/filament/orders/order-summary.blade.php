<div class="order-summary">
    <div class="item-label">
        {{ $itemsCount }} line item(s) · {{ $units }} unit(s)
    </div>

    <div class="details">
        <span class="label">Order total</span>
        <span class="value">₹ {{ number_format($total, 2) }}</span>
    </div>
</div>
