<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Invoice - {{ $orderNo }}</title>

    <style>
        @page {
            size: A4;
            margin: 12mm 13mm 17mm 13mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: "DejaVu Sans", Arial, sans-serif;
            font-size: 9px;
            line-height: 1.45;
            color: #252525;
            margin: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .right { text-align: right !important; }
        .center { text-align: center !important; }
        .bold { font-weight: bold; }

        .muted {
            color: #777;
        }

        /* Header */

        .header {
            margin-bottom: 17px;
            border-bottom: 2px solid #222;
        }

        .header td {
            padding-bottom: 11px;
            vertical-align: middle;
        }

        .logo {
            max-height: 42px;
            max-width: 150px;
        }

        .company {
            font-size: 17px;
            font-weight: bold;
            color: #222;
        }

        .invoice-title {
            font-size: 25px;
            font-weight: bold;
            letter-spacing: 1.5px;
            text-align: right;
        }

        .invoice-no {
            font-size: 10px;
            font-weight: bold;
            text-align: right;
            margin-top: 3px;
        }

        .invoice-date {
            font-size: 9px;
            color: #777;
            text-align: right;
        }

        /* Section headings */

        .section-title {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.7px;
            color: #555;
            background: #f3f4f6;
            padding: 6px 8px;
            margin-bottom: 7px;
        }

        /* Customer information */

        .parties {
            margin-bottom: 17px;
        }

        .parties > tbody > tr > td {
            vertical-align: top;
            padding: 0 8px 0 0;
        }

        .parties > tbody > tr > td:last-child {
            padding-right: 0;
        }

        .customer-name {
            font-size: 10px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .party-content {
            padding: 0 8px;
        }

        .meta td {
            padding: 2px 0;
            vertical-align: top;
        }

        .meta td:first-child {
            color: #777;
            width: 65px;
        }

        .meta td:last-child {
            font-weight: bold;
        }

        /* Items */

        .items {
            table-layout: fixed;
            margin-bottom: 12px;
        }

        .items thead {
            display: table-header-group;
        }

        .items tr {
            page-break-inside: avoid;
        }

        .items th {
            background: #292929;
            color: #fff;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            padding: 9px 7px;
            text-align: left;
        }

        .items td {
            padding: 9px 7px;
            border-bottom: 1px solid #e8e8e8;
            vertical-align: middle;
            word-wrap: break-word;
        }

        .items tbody tr:nth-child(even) {
            background: #fafafa;
        }

        /* Summary */

        .summary {
            margin-top: 10px;
        }

        .summary > tbody > tr > td {
            vertical-align: top;
        }

        .amount-words {
            padding: 8px 10px 0 0;
            font-size: 9px;
        }

        .amount-words-label {
            font-size: 8px;
            color: #777;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .totals {
            background: #f8f8f8;
            padding: 8px;
        }

        .totals td {
            padding: 5px 7px;
        }

        .totals .grand td {
            border-top: 1px solid #333;
            padding-top: 9px;
            font-size: 13px;
            font-weight: bold;
            color: #111;
        }

        /* Footer */

        .footer {
            position: fixed;
            bottom: -8mm;
            left: 0;
            right: 0;
            border-top: 1px solid #ddd;
            padding-top: 7px;
            text-align: center;
            font-size: 8px;
            color: #777;
        }

        .footer-message {
            font-size: 10px;
            font-weight: bold;
            color: #333;
            margin-bottom: 2px;
        }

    </style>
</head>

<body>

    {{-- Header --}}

    <table class="header">
        <tr>
            <td style="width: 55%">
                @if ($logoPath)
                    <img src="{{ $logoPath }}"
                         alt="{{ config('app.name') }}"
                         class="logo">
                @else
                    <div class="company">
                        {{ config('app.name') }}
                    </div>
                @endif
            </td>

            <td style="width: 45%">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-no">#{{ $orderNo }}</div>
                <div class="invoice-date">
                    {{ $order->created_at?->format(\App\Support\DateFormats::date()) }}
                </div>
            </td>
        </tr>
    </table>

    {{-- Customer / Shipping / Order Information --}}

    <table class="parties">
        <tr>

            <td style="width: 34%">
                <div class="section-title">Bill To</div>

                <div class="party-content">
                    <div class="customer-name">
                        {{ $order->customer_name }}
                    </div>

                    @if ($order->customer_phone)
                        <div>{{ $order->customer_phone }}</div>
                    @endif

                    @if ($order->customer_email)
                        <div>{{ $order->customer_email }}</div>
                    @endif
                </div>
            </td>

            <td style="width: 33%">
                <div class="section-title">Ship To</div>

                <div class="party-content">
                    {{ $order->shipping_address ?: $order->customer_name }}
                </div>
            </td>

            <td style="width: 33%">
                <div class="section-title">Order Details</div>

                <div class="party-content">
                    <table class="meta">
                        <tr>
                            <td>Status</td>
                            <td>
                                {{ $order->status?->getLabel() ?? 'N/A' }}
                            </td>
                        </tr>

                        <tr>
                            <td>Payment</td>
                            <td>
                                {{ $order->payment_status?->getLabel() ?? 'N/A' }}
                            </td>
                        </tr>
                    </table>
                </div>
            </td>

        </tr>
    </table>

    {{-- Product Items --}}

    <table class="items">

        <thead>
            <tr>
                <th class="center" style="width: 6%">#</th>
                <th style="width: 44%">Description</th>
                <th class="right" style="width: 17%">Rate</th>
                <th class="center" style="width: 9%">Qty</th>
                <th class="right" style="width: 24%">Amount</th>
            </tr>
        </thead>

        <tbody>

            @forelse ($order->items as $index => $item)

                <tr>
                    <td class="center">{{ $index + 1 }}</td>

                    <td class="bold">
                        {{ $item->product_name }}
                    </td>

                    <td class="right">
                        ₹{{ number_format((float) $item->unit_price, 2) }}
                    </td>

                    <td class="center">
                        {{ $item->quantity }}
                    </td>

                    <td class="right bold">
                        ₹{{ number_format((float) $item->line_subtotal, 2) }}
                    </td>
                </tr>

            @empty

                <tr>
                    <td colspan="5" class="center muted">
                        No products found.
                    </td>
                </tr>

            @endforelse

        </tbody>

    </table>

    {{-- Amount in Words and Totals --}}

    <table class="summary">

        <tr>

            <td style="width: 55%" class="amount-words">

                @if ($amountInWords)

                    <div class="amount-words-label">
                        Amount in Words
                    </div>

                    <div class="bold">
                        {{ $amountInWords }}
                    </div>

                @endif

            </td>

            <td style="width: 45%">

                <table class="totals">

                    <tr>
                        <td>Subtotal</td>

                        <td class="right">
                            ₹{{ number_format((float) $order->subtotal, 2) }}
                        </td>
                    </tr>

                    <tr>
                        <td>Shipping</td>

                        <td class="right">
                            @if ((float) $order->shipping_amount > 0)
                                ₹{{ number_format((float) $order->shipping_amount, 2) }}
                            @else
                                Free
                            @endif
                        </td>
                    </tr>

                    <tr class="grand">
                        <td>Total</td>

                        <td class="right">
                            ₹{{ number_format($total, 2) }}
                        </td>
                    </tr>

                </table>

            </td>

        </tr>

    </table>

    {{-- Footer --}}

    <div class="footer">

        <div class="footer-message">
            Thank you for your order!
        </div>

        <div>
            {{ config('app.name') }} &nbsp;|&nbsp;
            We appreciate your business.
        </div>

    </div>

</body>
</html>
