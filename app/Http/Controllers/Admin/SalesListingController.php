<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSalesListingRequest;
use App\Models\SalesListing;
use App\Models\SalesOrder;
use App\Services\SalesListingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesListingController extends Controller
{
    private SalesListingService $salesListingService;

    private array $sortAllowlist = [
        'so_no', 'billing_date', 'transaction_type', 'customer_name',
        'po_no', 'sales_invoice_no', 'quotation_no', 'sales_agent',
        'initial_payment_status', 'final_payment_status',
        'actual_payment_remarks', 'sales_channel', 'vat_exclusive_total',
        'total_with_vat', 'due_date',
    ];

    public function __construct(SalesListingService $salesListingService)
    {
        $this->salesListingService = $salesListingService;
    }

    public function index()
    {
        return view('admin.sales-order.sales-listing', [
            'title' => 'Sales Listing',
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = SalesListing::query()
            ->with(['salesOrder.customer.salesAgent', 'salesOrder.items'])
            ->whereHas('salesOrder', fn ($q) => $q->where('status', '!=', 'Cancelled'));

        $this->applyPaymentFilter($query, $request);
        $this->applyPriceReferenceFilter($query, $request);
        $this->applyColumnSearches($query, $request);
        $this->applySort($query, $request);

        $perPage = 50;
        $page = (int) $request->query('page', 1);

        $paginator = $query->paginate($perPage, ['*'], 'page', $page);

        $listings = $paginator->getCollection()->map(function (SalesListing $listing) {
            return $this->normalizeListing($listing);
        });

        return response()->json([
            'listings' => $listings,
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ]);
    }

    public function metrics(): JsonResponse
    {
        return response()->json($this->salesListingService->getMetrics());
    }

    public function show(SalesListing $salesListing): JsonResponse
    {
        $salesListing->load(['salesOrder.customer.salesAgent', 'salesOrder.items']);

        return response()->json(['listing' => $this->normalizeListing($salesListing)]);
    }

    public function update(UpdateSalesListingRequest $request, SalesListing $salesListing): JsonResponse
    {
        $salesOrder = $salesListing->salesOrder;

        if ($salesOrder === null) {
            return response()->json(['message' => 'Related Sales Order not found.'], 404);
        }

        if ($salesOrder->status === 'Cancelled') {
            return response()->json(['message' => 'Cannot update a cancelled Sales Order listing.'], 409);
        }

        $validated = $request->validated();

        try {
            DB::transaction(function () use ($validated, $salesListing, $salesOrder, $request) {
                $billingDate = $validated['billing_date'] ?? $salesListing->billing_date?->toDateString();
                $terms = $salesOrder->terms_snapshot;

                $dueDate = $billingDate !== null
                    ? $this->salesListingService->calculateDueDate($billingDate, $terms)
                    : $salesListing->due_date?->toDateString();

                $salesListing->forceFill([
                    'billing_date' => $validated['billing_date'] ?? $salesListing->billing_date,
                    'due_date' => $dueDate,
                    'transaction_type' => $validated['transaction_type'] ?? $salesListing->transaction_type,
                    'po_no' => $validated['po_no'] ?? $salesListing->po_no,
                    'sales_invoice_no' => $validated['sales_invoice_no'] ?? $salesListing->sales_invoice_no,
                    'quotation_no' => $validated['quotation_no'] ?? $salesListing->quotation_no,
                    'initial_payment_status' => $validated['initial_payment_status'],
                    'final_payment_status' => $validated['final_payment_status'],
                    'actual_payment_remarks' => $validated['actual_payment_remarks'] ?? $salesListing->actual_payment_remarks,
                    'updated_by' => $request->user()->login_ID,
                ])->save();

                if (isset($validated['sales_channel'])) {
                    $salesOrder->forceFill([
                        'sales_channel' => $validated['sales_channel'],
                    ])->save();
                }
            });

            $salesListing->refresh();
            $salesListing->load(['salesOrder.customer.salesAgent', 'salesOrder.items']);

            return response()->json([
                'message' => 'Sales Listing Updated Successfully',
                'listing' => $this->normalizeListing($salesListing),
                'metrics' => $this->salesListingService->getMetrics(),
            ]);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update Sales Listing: ' . $e->getMessage()], 500);
        }
    }

    private function normalizeListing(SalesListing $listing): array
    {
        $order = $listing->salesOrder;

        return [
            'id' => $listing->id,
            'sales_order_id' => $listing->sales_order_id,
            'so_no' => $order?->so_no ?? '--',
            'billing_date' => $listing->billing_date?->toDateString(),
            'due_date' => $listing->due_date?->toDateString(),
            'transaction_type' => $listing->transaction_type,
            'po_no' => $listing->po_no,
            'sales_invoice_no' => $listing->sales_invoice_no,
            'quotation_no' => $listing->quotation_no,
            'initial_payment_status' => $listing->initial_payment_status,
            'final_payment_status' => $listing->final_payment_status,
            'actual_payment_remarks' => $listing->actual_payment_remarks,
            'customer_name' => $order?->customer_name_snapshot ?? '--',
            'sales_agent' => $this->salesListingService->resolveSalesAgentName($order),
            'price_reference' => $order?->price_reference_snapshot ?? 'green',
            'sales_channel' => $order?->sales_channel ?? '--',
            'terms_snapshot' => $order?->terms_snapshot,
            'vat_exclusive_total' => (float) ($order?->vat_exclusive_total ?? 0),
            'total_with_vat' => (float) ($order?->total_with_vat ?? 0),
        ];
    }

    private function applyPaymentFilter(Builder $query, Request $request): void
    {
        $filter = $request->query('payment_filter', 'all');
        $today = now()->toDateString();

        match ($filter) {
            'paid' => $query->where('final_payment_status', 'paid'),
            'unpaid' => $query->where('final_payment_status', 'unpaid')
                ->where(function ($q) use ($today) {
                    $q->whereNull('due_date')
                      ->orWhere('due_date', '>=', $today);
                }),
            'overdue' => $query->where('final_payment_status', 'unpaid')
                ->where('due_date', '<', $today),
            default => null,
        };
    }

    private function applyPriceReferenceFilter(Builder $query, Request $request): void
    {
        $filter = $request->query('price_filter', 'all');

        if (in_array($filter, ['green', 'yellow'], true)) {
            $query->whereHas('salesOrder', function ($q) use ($filter) {
                $q->where('price_reference_snapshot', $filter);
            });
        }
    }

    private function applyColumnSearches(Builder $query, Request $request): void
    {
        $searches = $request->query('search', []);

        if (! is_array($searches)) {
            return;
        }

        foreach ($searches as $column => $term) {
            $term = trim((string) $term);

            if ($term === '') {
                continue;
            }

            match ($column) {
                'so_no' => $query->whereHas('salesOrder', fn ($q) => $q->where('so_no', 'like', "%{$term}%")),
                'billing_date' => $query->where('billing_date', 'like', "%{$term}%"),
                'vat_exclusive_total' => $query->whereHas('salesOrder', fn ($q) => $q->where('vat_exclusive_total', 'like', "%{$term}%")),
                'total_with_vat' => $query->whereHas('salesOrder', fn ($q) => $q->where('total_with_vat', 'like', "%{$term}%")),
                'transaction_type' => $query->where('transaction_type', 'like', "%{$term}%"),
                'customer_name' => $query->whereHas('salesOrder', fn ($q) => $q->where('customer_name_snapshot', 'like', "%{$term}%")),
                'po_no' => $query->where('po_no', 'like', "%{$term}%"),
                'sales_invoice_no' => $query->where('sales_invoice_no', 'like', "%{$term}%"),
                'quotation_no' => $query->where('quotation_no', 'like', "%{$term}%"),
                'sales_agent' => $query->whereHas('salesOrder', function ($q) use ($term) {
                    $q->where('sales_agent_snapshot', 'like', "%{$term}%")
                      ->orWhereHas('customer.salesAgent', fn ($a) => $a->where('name', 'like', "%{$term}%"));
                }),
                'initial_payment_status' => $query->where('initial_payment_status', 'like', "%{$term}%"),
                'final_payment_status' => $query->where('final_payment_status', 'like', "%{$term}%"),
                'actual_payment_remarks' => $query->where('actual_payment_remarks', 'like', "%{$term}%"),
                'sales_channel' => $query->whereHas('salesOrder', fn ($q) => $q->where('sales_channel', 'like', "%{$term}%")),
                default => null,
            };
        }
    }

    private function applySort(Builder $query, Request $request): void
    {
        $sort = $request->query('sort', '');
        $direction = $request->query('direction', '');

        if (! in_array($sort, $this->sortAllowlist, true) || ! in_array($direction, ['asc', 'desc'], true)) {
            $query->whereHas('salesOrder', fn ($q) => $q->orderByDesc('created_at'))->orderByDesc('id');

            return;
        }

        $sortMap = [
            'so_no' => 'salesOrder.so_no',
            'billing_date' => 'sales_listings.billing_date',
            'transaction_type' => 'sales_listings.transaction_type',
            'customer_name' => 'salesOrder.customer_name_snapshot',
            'po_no' => 'sales_listings.po_no',
            'sales_invoice_no' => 'sales_listings.sales_invoice_no',
            'quotation_no' => 'sales_listings.quotation_no',
            'sales_agent' => 'salesOrder.sales_agent_snapshot',
            'initial_payment_status' => 'sales_listings.initial_payment_status',
            'final_payment_status' => 'sales_listings.final_payment_status',
            'actual_payment_remarks' => 'sales_listings.actual_payment_remarks',
            'sales_channel' => 'salesOrder.sales_channel',
            'vat_exclusive_total' => 'salesOrder.vat_exclusive_total',
            'total_with_vat' => 'salesOrder.total_with_vat',
            'due_date' => 'sales_listings.due_date',
        ];

        $mapped = $sortMap[$sort] ?? null;

        if ($mapped === null) {
            $query->whereHas('salesOrder', fn ($q) => $q->orderByDesc('created_at'))->orderByDesc('id');

            return;
        }

        if (str_starts_with($mapped, 'salesOrder.')) {
            $column = substr($mapped, 11);
            $query->whereHas('salesOrder', fn ($q) => $q->orderBy($column, $direction));
        } else {
            $query->orderBy($mapped, $direction);
        }

        $query->orderBy('sales_listings.id', $direction);
    }
}
