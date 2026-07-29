@php
    $items = $quotation->items ?? collect();
    $rowCount = max(25, $items->count());
    $previewMode = $previewMode ?? false;
    $logoUrl = $logoUrl ?? asset(config('company.logo'));
@endphp

<div class="qp-sheet">
    {{-- HEADER --}}
    <section class="qp-top">
        <div class="qp-left">
            <div class="qp-company">
                <img class="qp-logo" src="{{ $logoUrl }}" alt="{{ config('company.name') }} logo">
                <div>
                    <h1 class="qp-company-name">{{ config('company.name') }}</h1>
                    <div class="qp-company-lines">
                        <div>{{ config('company.tin') }}</div>
                        <div>{{ config('company.address') }}</div>
                        <div>{{ config('company.contact') }}</div>
                    </div>
                </div>
            </div>
        </div>
        <aside class="qp-right">
            <div class="qp-title">Sales Quotation</div>
            <div class="qp-info-grid">
                <div class="qp-info-label">Quotation No.:</div>
                <div class="qp-info-value">{{ $quotation->quotation_no ?? '(Auto)' }}</div>

                <div class="qp-info-label">Date:</div>
                <div class="qp-info-value">{{ $quotation->quotation_date?->toDateString() ?? date('Y-m-d') }}</div>

                <div class="qp-info-label">Prepared by:</div>
                <div class="qp-info-value">{{ $quotation->prepared_by_name_snapshot ?? '—' }}</div>

                <div class="qp-info-label">Time:</div>
                <div class="qp-info-value">{{ now()->format('h:i A') }}</div>

                @if (!empty($quotation->attention_to))
                <div class="qp-info-label">Attention:</div>
                <div class="qp-info-value">{{ $quotation->attention_to }}</div>
                @endif
            </div>
        </aside>
    </section>

    {{-- SOLD TO --}}
    <section class="qp-sold-to">
        <div class="qp-sold-row">
            <div class="qp-sold-label">Customer Name:</div>
            <div class="qp-sold-value">{{ strtoupper($quotation->customer_name_snapshot ?? '') }}</div>
        </div>
        <div class="qp-sold-row">
            <div class="qp-sold-label">TIN No:</div>
            <div class="qp-sold-value">{{ $quotation->tin_snapshot ?? '' }}</div>
        </div>
        <div class="qp-sold-row">
            <div class="qp-sold-label">Address:</div>
            <div class="qp-sold-value">{{ strtoupper($quotation->address_snapshot ?? '') }}</div>
        </div>
    </section>

    {{-- ITEMS TABLE --}}
    <table class="qp-order-table">
        <thead>
            <tr>
                <th style="width:12mm;">Item No:</th>
                <th>Item Description</th>
                <th style="width:12mm;">Qty</th>
                <th style="width:14mm;">Unit</th>
                <th style="width:23mm;">Unit Price</th>
                <th style="width:25mm;">Total Price</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $i => $item)
                @php
                    $displayPrice = (float) ($item->unit_price_with_tax ?? 0);
                    $displayTotal = (float) ($item->line_total ?? 0);
                    $description = strtoupper(trim(($item->item_name_snapshot ?? '') . ' ' . ($item->brand_snapshot ?? '')));
                @endphp
                <tr>
                    <td class="qp-c">{{ $i + 1 }}</td>
                    <td class="qp-desc">{{ $description }}</td>
                    <td class="qp-c">{{ number_format((float) $item->quantity, 0) }}</td>
                    <td class="qp-c">{{ $item->unit_snapshot ?? '' }}</td>
                    <td class="qp-r">{{ number_format($displayPrice, 2) }}</td>
                    <td class="qp-r">{{ number_format($displayTotal, 2) }}</td>
                </tr>
            @empty
                @for ($i = 0; $i < 25; $i++)
                <tr><td class="qp-c"></td><td></td><td></td><td></td><td></td><td></td></tr>
                @endfor
            @endforelse
            @for ($i = $items->count(); $i < 25; $i++)
                <tr><td class="qp-c"></td><td></td><td></td><td></td><td></td><td></td></tr>
            @endfor
        </tbody>
    </table>

    {{-- BOTTOM: TOTAL + SIGNATURES --}}
    <section class="qp-bottom">
        <div class="qp-total-box">
            <div class="qp-total-row qp-total-row--green">
                <div class="qp-total-label">TOTAL AMOUNT</div>
                <div class="qp-amount">{{ number_format((float) ($quotation->grand_total ?? 0), 2) }}</div>
            </div>
            <div class="qp-total-row">
                <div class="qp-sign-label">PREPARED BY:</div>
                <div></div>
            </div>
            <div class="qp-total-row">
                <div class="qp-sign-label">CHECKED BY:</div>
                <div></div>
            </div>
            <div class="qp-total-row">
                <div class="qp-sign-label">NOTED BY:</div>
                <div></div>
            </div>
        </div>
    </section>

    {{-- TERMS & CONDITIONS --}}
    @php
        $hasTerms = $quotation->payment_terms || $quotation->delivery_terms || $quotation->cancellation_terms || $quotation->warranty || $quotation->mode_of_payment || $quotation->lead_time_at || $quotation->valid_until;
    @endphp
    @if ($hasTerms)
    <section class="qp-terms">
        <h3>Terms &amp; Conditions</h3>
        <ul>
            @if ($quotation->lead_time_at)<li>Lead Time: {{ $quotation->lead_time_at instanceof \Carbon\Carbon ? $quotation->lead_time_at->toDateString() : $quotation->lead_time_at }}</li>@endif
            @if ($quotation->valid_until)<li>Offer Valid Until: {{ $quotation->valid_until instanceof \Carbon\Carbon ? $quotation->valid_until->toDateString() : $quotation->valid_until }}</li>@endif
            @if ($quotation->payment_terms)<li>Payment Terms: {{ $quotation->payment_terms }}</li>@endif
            @if ($quotation->mode_of_payment)<li>Mode of Payment: {{ $quotation->mode_of_payment }}</li>@endif
            @if ($quotation->delivery_terms)<li>Delivery Terms: {{ $quotation->delivery_terms }}</li>@endif
            @if ($quotation->cancellation_terms)<li>Cancellation Terms: {{ $quotation->cancellation_terms }}</li>@endif
            @if ($quotation->warranty)<li>Warranty: {{ $quotation->warranty }}</li>@endif
        </ul>
    </section>
    @endif
</div>
