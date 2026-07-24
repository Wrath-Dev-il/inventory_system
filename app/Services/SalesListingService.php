<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\SalesListing;
use App\Models\SalesOrder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SalesListingService
{
    public function calculateDueDate(?string $billingDate, ?string $terms): ?string
    {
        if ($billingDate === null) {
            return null;
        }

        $days = $this->parseTermsDays($terms);

        if ($days === null) {
            return $billingDate;
        }

        if ($days <= 0) {
            return $billingDate;
        }

        return now()->parse($billingDate)->addDays($days)->toDateString();
    }

    public function parseTermsDays(?string $terms): ?int
    {
        if ($terms === null || trim($terms) === '') {
            return null;
        }

        $normalized = strtolower(trim($terms));

        if ($normalized === 'cash') {
            return 0;
        }

        if (preg_match('/^(\d+)\s*days?$/i', $normalized, $matches)) {
            return (int) $matches[1];
        }

        if (preg_match('/^(\d+)-day$/i', $normalized, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    public function resolveSalesAgentName(SalesOrder $order): string
    {
        if (! empty($order->sales_agent_snapshot)) {
            return $order->sales_agent_snapshot;
        }

        $customer = $order->customer;

        if ($customer && $customer->salesAgent) {
            return $customer->salesAgent->name;
        }

        if ($customer && ! empty($customer->salesman_name)) {
            return $customer->salesman_name;
        }

        return '--';
    }

    public function backfillExistingOrders(): int
    {
        $count = 0;

        DB::transaction(function () use (&$count) {
            $orders = SalesOrder::query()
                ->where('status', 'Confirmed')
                ->whereDoesntHave('salesListing')
                ->get();

            foreach ($orders as $order) {
                $dueDate = $this->calculateDueDate(
                    $order->order_date?->toDateString(),
                    $order->terms_snapshot
                );

                SalesListing::query()->create([
                    'sales_order_id' => $order->id,
                    'billing_date' => $order->order_date?->toDateString(),
                    'due_date' => $dueDate,
                    'transaction_type' => 'vat_inc',
                    'initial_payment_status' => 'unpaid',
                    'final_payment_status' => 'unpaid',
                ]);

                $count++;
            }
        });

        return $count;
    }

    public function createForOrder(SalesOrder $order): SalesListing
    {
        $dueDate = $this->calculateDueDate(
            $order->order_date?->toDateString(),
            $order->terms_snapshot
        );

        return SalesListing::query()->firstOrCreate(
            ['sales_order_id' => $order->id],
            [
                'billing_date' => $order->order_date?->toDateString(),
                'due_date' => $dueDate,
                'transaction_type' => 'vat_inc',
                'initial_payment_status' => 'unpaid',
                'final_payment_status' => 'unpaid',
            ]
        );
    }

    public function getMetrics(): array
    {
        $now = now()->toDateString();

        $paid = SalesListing::query()
            ->where('final_payment_status', 'paid')
            ->whereHas('salesOrder', fn ($q) => $q->where('status', '!=', 'Cancelled'))
            ->count();

        $overdue = SalesListing::query()
            ->where('final_payment_status', 'unpaid')
            ->where('due_date', '<', $now)
            ->whereHas('salesOrder', fn ($q) => $q->where('status', '!=', 'Cancelled'))
            ->count();

        $unpaid = SalesListing::query()
            ->where('final_payment_status', 'unpaid')
            ->where(function ($q) use ($now) {
                $q->whereNull('due_date')
                  ->orWhere('due_date', '>=', $now);
            })
            ->whereHas('salesOrder', fn ($q) => $q->where('status', '!=', 'Cancelled'))
            ->count();

        return [
            'paid' => $paid,
            'unpaid' => $unpaid,
            'overdue' => $overdue,
        ];
    }
}
