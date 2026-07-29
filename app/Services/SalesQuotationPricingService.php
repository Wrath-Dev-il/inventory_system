<?php

namespace App\Services;

class SalesQuotationPricingService
{
    public const VAT_RATE = 0.12;

    public function vatMultiplier(): float
    {
        return 1.0 + self::VAT_RATE;
    }

    public function calculateWithoutVat(float $vatInclusivePrice): float
    {
        return round($vatInclusivePrice / $this->vatMultiplier(), 2);
    }

    public function calculateWithVat(float $priceWithoutVat): float
    {
        return round($priceWithoutVat * $this->vatMultiplier(), 2);
    }

    public function buildItemData(array $product, float $quantity, string $offerDescription, float $unitPrice, float $discountPercent = 0): array
    {
        $unitPriceWithTax = $this->calculateWithVat($unitPrice);
        $taxAmount = round($unitPriceWithTax - $unitPrice, 2);
        $lineTotal = round($quantity * $unitPriceWithTax, 2);

        return [
            'product_id' => $product['id'],
            'item_no_snapshot' => $product['item_no'] ?? null,
            'item_name_snapshot' => $product['product'] ?? null,
            'brand_snapshot' => $product['brand'] ?? null,
            'unit_snapshot' => $product['unit'] ?? null,
            'available_quantity_snapshot' => (float) ($product['qty'] ?? 0),
            'quantity' => $quantity,
            'offer_description' => $offerDescription,
            'discount_percent' => $discountPercent,
            'unit_price_without_tax_snapshot' => $unitPrice,
            'unit_price' => $unitPrice,
            'unit_price_with_tax' => $unitPriceWithTax,
            'tax_amount' => $taxAmount,
            'line_total' => $lineTotal,
        ];
    }

    public function calculateTotals(array $items): array
    {
        $subtotal = 0;
        $taxAmount = 0;
        $grandTotal = 0;

        foreach ($items as $item) {
            $lineTotal = (float) ($item['line_total'] ?? 0);
            $itemTax = (float) ($item['tax_amount'] ?? 0);
            $grandTotal += $lineTotal;
            $taxAmount += $itemTax;
        }

        $grandTotal = round($grandTotal, 2);
        $taxAmount = round($taxAmount, 2);
        $subtotal = round($grandTotal - $taxAmount, 2);

        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
        ];
    }
}
