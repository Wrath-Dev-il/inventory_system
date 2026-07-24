@php
    $isVat = ($mode ?? 'vat') === 'vat';
    $items = $order->items ?? collect();
    $rowCount = max(25, $items->count());
    $totalWithVat = (float) $order->total_with_vat;
    $vatExclusive = (float) $order->vat_exclusive_total;
    $vatAmt = (float) $order->vat_amount;
    $displayTotal = $isVat ? $vatExclusive : $totalWithVat;
    $docTitle = $isVat ? 'A-Sales Order' : 'Sales Invoice';
    $transactionType = $isVat ? 'VAT EX' : 'NO VAT';
    $totalLabel = $isVat ? 'VAT EX TOTAL' : 'TOTAL AMOUNT';
    $fmt = fn($v) => '₱' . number_format((float) $v, 2);
@endphp

<main class="sheet">
    <section class="top">
        <div>
            <div class="company">
                <img class="company-logo" src="{{ asset('images/login/logo.png') }}" alt="CONTROL A logo">
                <div>
                    <h1 class="company-name">CONTROL A TRADING AND SERVICES CORP.</h1>
                    <div class="company-lines">
                        <div>601-163-860-00000</div>
                        <div>728 GENERAL LUIS ST. CAYBIGA CALOOCAN CITY</div>
                        <div>0945 825 8802</div>
                    </div>
                </div>
            </div>
        </div>

        <aside>
            <div class="doc-title">{{ $docTitle }}</div>
            <div class="info-grid">
                <div class="label">Transaction Type:</div><div class="value">{{ $transactionType }}</div>
                <div class="label">S.O. No.:</div><div class="value">{{ $order->so_no }}</div>
                <div class="label">Date:</div><div class="value">{{ $order->order_date?->format('M d, Y') ?? now()->format('M d, Y') }}</div>
                <div class="label">Prepared by:</div><div class="value">{{ $order->prepared_by_name_snapshot ?? '--' }}</div>
                <div class="label">Sales Channel:</div><div class="value">{{ strtoupper($order->sales_channel ?? '--') }}</div>
                <div class="label">Payment Status:</div><div class="value">{{ strtoupper($order->payment_status ?? '--') }}</div>
                <div class="label">Time:</div><div class="value">{{ now()->format('h:i:s A') }}</div>
            </div>
        </aside>
    </section>

    <section class="sold-to">
        <div class="sold-row">
            <div class="sold-label">Sold To:</div>
            <div class="sold-value">{{ strtoupper($order->customer_name_snapshot ?? '') }}</div>
        </div>
        <div class="sold-row">
            <div class="sold-label">TIN No:</div>
            <div class="sold-value">{{ $order->tin_snapshot ?? '' }}</div>
        </div>
        <div class="sold-row">
            <div class="sold-label">Address:</div>
            <div class="sold-value">{{ strtoupper($order->address_snapshot ?? '') }}</div>
        </div>
    </section>

    <table class="order-table">
        <thead>
            <tr>
                <th style="width: 10mm;">Item No:</th>
                <th>Item Description</th>
                <th style="width: 10mm;">Qty</th>
                <th style="width: 12mm;">Unit</th>
                <th style="width: 10mm;">Disc%</th>
                <th style="width: 22mm;">Unit Price</th>
                <th style="width: 22mm;">Total Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($items as $index => $item)
                @php
                    $unitPrice = (float) ($item->selling_price_snapshot ?? 0);
                    $lineTotal = (float) ($item->line_total_with_vat ?? 0);
                    $discPct = (float) ($item->discount_percent_snapshot ?? 0);
                @endphp
                <tr>
                    <td class="center">{{ $index + 1 }}</td>
                    <td class="desc">{{ strtoupper(trim(($item->product_name_snapshot ?? '') . ' ' . ($item->brand_snapshot ?? ''))) }}</td>
                    <td class="center">{{ number_format($item->ordered_qty, 2) }}</td>
                    <td class="center">{{ $item->unit_snapshot ?? '' }}</td>
                    <td class="center">{{ $discPct > 0 ? number_format($discPct, 2) . '%' : '' }}</td>
                    <td class="right">{{ $fmt($unitPrice) }}</td>
                    <td class="right">{{ $fmt($lineTotal) }}</td>
                </tr>
            @endforeach
            @for ($line = $items->count() + 1; $line <= $rowCount; $line++)
                <tr>
                    <td class="center"></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    <section class="bottom">
        <div class="total-box">
            <div class="total-row total-row--green">
                <div>{{ $totalLabel }}</div>
                <div class="amount">{{ $fmt($displayTotal) }}</div>
            </div>
            @if ($isVat)
                <div class="total-row">
                    <div>VAT AMOUNT</div>
                    <div class="amount">{{ $fmt($vatAmt) }}</div>
                </div>
            @endif
            <div class="total-row">
                <div class="sign-label">Prepared By:</div>
                <div></div>
            </div>
            <div class="total-row">
                <div class="sign-label">Checked By:</div>
                <div></div>
            </div>
        </div>
    </section>
</main>
