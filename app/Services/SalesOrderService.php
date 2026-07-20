<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\SalesOrderSequence;
use App\Models\SalesOrderStatusLog;
use App\Models\Product;
use App\Models\Login;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public const VAT_RATE = 0.12;

    public function vatMultiplier(): float
    {
        return 1.0 + self::VAT_RATE;
    }

    public function generateSoNo(): string
    {
        $monthYear = now()->format('mY');
        $monthYearKey = now()->format('mY');

        return DB::transaction(function () use ($monthYear, $monthYearKey) {
            $sequence = SalesOrderSequence::query()
                ->lockForUpdate()
                ->where('month_year', $monthYearKey)
                ->first();

            if (! $sequence) {
                $sequence = SalesOrderSequence::query()->create([
                    'month_year' => $monthYearKey,
                    'last_sequence' => 0,
                ]);
            }

            $sequence->increment('last_sequence');
            $seq = $sequence->fresh()->last_sequence;

            return $monthYear.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
        });
    }

    public function calculateWithoutVat(float $vatInclusivePrice): float
    {
        return round($vatInclusivePrice / $this->vatMultiplier(), 2);
    }

    public function calculateVatAmount(float $vatInclusiveTotal, float $vatExclusiveTotal): float
    {
        return round($vatInclusiveTotal - $vatExclusiveTotal, 2);
    }

    public function buildItemData(Product $product, float $orderedQty, float $discountPercent): array
    {
        $sellingPrice = (float) $product->selling_price;
        $unitPriceWithoutVat = $this->calculateWithoutVat($sellingPrice);

        $lineTotalWithVat = round($orderedQty * $sellingPrice, 2);
        $lineTotalWithoutVat = round($orderedQty * $unitPriceWithoutVat, 2);
        $vatAmount = $this->calculateVatAmount($lineTotalWithVat, $lineTotalWithoutVat);

        if ($discountPercent > 0) {
            $discountMultiplier = $discountPercent / 100;
            $lineTotalWithVat = round($lineTotalWithVat * (1 - $discountMultiplier), 2);
            $lineTotalWithoutVat = round($lineTotalWithoutVat * (1 - $discountMultiplier), 2);
            $vatAmount = $this->calculateVatAmount($lineTotalWithVat, $lineTotalWithoutVat);
        }

        return [
            'product_id' => $product->id,
            'item_no_snapshot' => $product->item_no,
            'product_name_snapshot' => $product->product,
            'brand_snapshot' => $product->brand,
            'unit_snapshot' => $product->unit,
            'ordered_qty' => $orderedQty,
            'selling_price_snapshot' => $sellingPrice,
            'discount_percent_snapshot' => $discountPercent,
            'unit_price_without_vat' => $unitPriceWithoutVat,
            'line_total_without_vat' => $lineTotalWithoutVat,
            'vat_amount' => $vatAmount,
            'line_total_with_vat' => $lineTotalWithVat,
        ];
    }

    public function calculateTotals(array $items): array
    {
        $totalWithVat = 0;
        $totalQty = 0;

        foreach ($items as $item) {
            $totalWithVat += (float) $item['line_total_with_vat'];
            $totalQty += (float) $item['ordered_qty'];
        }

        $totalWithVat = round($totalWithVat, 2);
        $vatAmount = round(($totalWithVat / $this->vatMultiplier()) * self::VAT_RATE, 2);
        $vatExclusiveTotal = round($totalWithVat - $vatAmount, 2);

        return [
            'total_with_vat' => $totalWithVat,
            'total_without_vat' => $vatExclusiveTotal,
            'vat_exclusive_total' => $vatExclusiveTotal,
            'vat_amount' => $vatAmount,
            'total_ordered_qty' => round($totalQty, 2),
        ];
    }

    public function createSalesOrder(array $data, Login $user, array $items): SalesOrder
    {
        return DB::transaction(function () use ($data, $user, $items) {
            $soNo = $this->generateSoNo();

            $itemRows = [];
            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);

                if ((float) $product->qty < (float) $item['ordered_qty']) {
                    throw new \RuntimeException(
                        "Insufficient stock for product {$product->product}. Available: {$product->qty}, requested: {$item['ordered_qty']}."
                    );
                }

                $itemRows[] = $this->buildItemData($product, (float) $item['ordered_qty'], (float) ($item['discount_percent'] ?? 0));

                $product->decrement('qty', (float) $item['ordered_qty']);
            }

            $totals = $this->calculateTotals($itemRows);

            $order = SalesOrder::query()->create([
                'so_no' => $soNo,
                'customer_id' => $data['customer_id'],
                'customer_no_snapshot' => $data['customer_no_snapshot'] ?? null,
                'customer_name_snapshot' => $data['customer_name_snapshot'] ?? null,
                'tin_snapshot' => $data['tin_snapshot'] ?? null,
                'address_snapshot' => $data['address_snapshot'] ?? null,
                'price_reference_snapshot' => $data['price_reference_snapshot'] ?? null,
                'sales_agent_snapshot' => $data['sales_agent_snapshot'] ?? null,
                'salesman_snapshot' => $data['salesman_snapshot'] ?? null,
                'terms_snapshot' => $data['terms_snapshot'] ?? null,
                'sales_channel' => $data['sales_channel'],
                'order_date' => now()->toDateString(),
                'prepared_by_user_id' => $user->login_ID,
                'prepared_by_name_snapshot' => $user->display_name ?: $user->User_ID,
                'payment_status' => 'Unpaid',
                'status' => 'Confirmed',
                'total_ordered_qty' => $totals['total_ordered_qty'],
                'total_without_vat' => $totals['total_without_vat'],
                'vat_exclusive_total' => $totals['vat_exclusive_total'],
                'vat_amount' => $totals['vat_amount'],
                'total_with_vat' => $totals['total_with_vat'],
                'confirmed_at' => now(),
            ]);

            foreach ($itemRows as $row) {
                $order->items()->create($row);
            }

            SalesOrderStatusLog::query()->create([
                'sales_order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'Confirmed',
                'changed_by' => $user->login_ID,
                'remarks' => 'Sales Order created',
            ]);

            return $order->fresh(['items', 'customer', 'preparedBy']);
        });
    }
}
