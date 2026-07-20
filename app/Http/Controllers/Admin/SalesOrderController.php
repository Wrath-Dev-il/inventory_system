<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderStatusLog;
use App\Services\SalesOrderService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SalesOrderController extends Controller
{
    private SalesOrderService $salesOrderService;

    private array $sortAllowlist = [
        'so_no', 'customer_name_snapshot', 'total_with_vat',
        'total_ordered_qty', 'order_date', 'status', 'payment_status',
    ];

    private array $mainColumns = [
        'so_no', 'customer_name_snapshot', 'total_with_vat',
        'total_ordered_qty', 'order_date', 'status', 'payment_status',
    ];

    public function __construct(SalesOrderService $salesOrderService)
    {
        $this->salesOrderService = $salesOrderService;
    }

    public function index(Request $request)
    {
        $query = SalesOrder::query()->with(['items', 'customer', 'preparedBy']);

        $this->applyMainSearch($query, $request);
        $this->applySort($query, $request);

        $priceFilter = $request->query('price_filter', '');
        if (in_array($priceFilter, ['green', 'yellow'], true)) {
            $query->where('price_reference_snapshot', $priceFilter);
        }

        $salesOrders = $query->paginate(50)->withQueryString();

        return view('admin.sales-order.sales-order', [
            'title' => 'Sales Orders',
            'subtitle' => 'Manage sales orders and invoices.',
            'salesOrders' => $salesOrders,
            'searches' => $request->query('search', []),
            'globalSearch' => $request->query('q', ''),
            'priceFilter' => $priceFilter,
            'sortField' => $request->query('sort', ''),
            'sortDir' => $request->query('direction', ''),
            'salesOrderStoreUrl' => route('admin.sales-order.store'),
            'salesOrderUpdateUrl' => route('admin.sales-order.update', ['sales_order' => '__SALES_ORDER_ID__']),
            'salesOrderShowUrlTemplate' => route('admin.sales-order.show', ['sales_order' => '__SALES_ORDER_ID__']),
            'salesOrderPrintSalesOrderUrl' => route('admin.sales-order.print-sales-order', ['sales_order' => '__SALES_ORDER_ID__']),
            'salesOrderPrintSalesInvoiceUrl' => route('admin.sales-order.print-sales-invoice', ['sales_order' => '__SALES_ORDER_ID__']),
            'salesOrderPrintBothUrl' => route('admin.sales-order.print-both', ['sales_order' => '__SALES_ORDER_ID__']),
            'customersJson' => $this->customersJson(),
            'productsJson' => $this->productsJson(),
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'sales_channel' => ['required', 'string', Rule::in(['Caloocan', 'Laguna'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.ordered_qty' => ['required', 'numeric', 'gt:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();

        $customer = Customer::with(['priceReference', 'salesAgent', 'customerAddress'])->findOrFail($request->customer_id);

        $customerData = [
            'customer_id' => $customer->id,
            'customer_no_snapshot' => $customer->customer_no,
            'customer_name_snapshot' => $customer->customer_name,
            'tin_snapshot' => $customer->tin,
            'address_snapshot' => $customer->customerAddress?->formatted_address,
            'price_reference_snapshot' => strtolower((string) ($customer->priceReference?->code ?? 'green')),
            'sales_agent_snapshot' => $customer->salesAgent?->name,
            'salesman_snapshot' => $customer->salesman_name,
            'terms_snapshot' => $customer->terms,
            'sales_channel' => $request->sales_channel,
        ];

        try {
            $order = $this->salesOrderService->createSalesOrder($customerData, $user, $request->items);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        return response()->json([
            'message' => 'Sales Order Created Successfully',
            'sales_order' => $this->normalizeOrder($order),
        ], 201);
    }

    public function show(SalesOrder $salesOrder): JsonResponse
    {
        $salesOrder->load(['items.product', 'customer', 'preparedBy']);

        return response()->json(['sales_order' => $this->normalizeOrder($salesOrder)]);
    }

    public function update(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        if ($salesOrder->payment_status === 'Paid') {
            return response()->json(['message' => 'Paid orders cannot be edited.'], 409);
        }

        $request->validate([
            'customer_id' => ['required', 'integer', 'exists:customers,id'],
            'sales_channel' => ['required', 'string', Rule::in(['Caloocan', 'Laguna'])],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.ordered_qty' => ['required', 'numeric', 'gt:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();

        try {
            DB::transaction(function () use ($request, $salesOrder, $user) {
            if ($salesOrder->status === 'Confirmed') {
                foreach ($salesOrder->items as $oldItem) {
                    Product::query()->where('id', $oldItem->product_id)->increment('qty', (float) $oldItem->ordered_qty);
                }
            }

            $customer = Customer::with(['priceReference', 'salesAgent', 'customerAddress'])->findOrFail($request->customer_id);

            $salesOrder->items()->delete();

            $customerData = [
                'customer_id' => $customer->id,
                'customer_no_snapshot' => $customer->customer_no,
                'customer_name_snapshot' => $customer->customer_name,
                'tin_snapshot' => $customer->tin,
                'address_snapshot' => $customer->customerAddress?->formatted_address,
                'price_reference_snapshot' => strtolower((string) ($customer->priceReference?->code ?? 'green')),
                'sales_agent_snapshot' => $customer->salesAgent?->name,
                'salesman_snapshot' => $customer->salesman_name,
                'terms_snapshot' => $customer->terms,
                'sales_channel' => $request->sales_channel,
            ];

            $itemRows = [];
            foreach ($request->items as $item) {
                $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);

                if ((float) $product->qty < (float) $item['ordered_qty']) {
                    throw new \RuntimeException(
                        "Insufficient stock for product {$product->product}. Available: {$product->qty}, requested: {$item['ordered_qty']}."
                    );
                }

                $itemRows[] = $this->salesOrderService->buildItemData(
                    $product,
                    (float) $item['ordered_qty'],
                    (float) ($item['discount_percent'] ?? 0)
                );

                $product->decrement('qty', (float) $item['ordered_qty']);
            }

            $totals = $this->salesOrderService->calculateTotals($itemRows);

            $salesOrder->update([
                'customer_id' => $customerData['customer_id'],
                'customer_no_snapshot' => $customerData['customer_no_snapshot'],
                'customer_name_snapshot' => $customerData['customer_name_snapshot'],
                'tin_snapshot' => $customerData['tin_snapshot'],
                'address_snapshot' => $customerData['address_snapshot'],
                'price_reference_snapshot' => $customerData['price_reference_snapshot'],
                'sales_agent_snapshot' => $customerData['sales_agent_snapshot'],
                'salesman_snapshot' => $customerData['salesman_snapshot'],
                'terms_snapshot' => $customerData['terms_snapshot'],
                'sales_channel' => $customerData['sales_channel'],
                'total_ordered_qty' => $totals['total_ordered_qty'],
                'total_without_vat' => $totals['total_without_vat'],
                'vat_exclusive_total' => $totals['vat_exclusive_total'],
                'vat_amount' => $totals['vat_amount'],
                'total_with_vat' => $totals['total_with_vat'],
            ]);

            foreach ($itemRows as $row) {
                $salesOrder->items()->create($row);
            }

            SalesOrderStatusLog::query()->create([
                'sales_order_id' => $salesOrder->id,
                'from_status' => $salesOrder->status,
                'to_status' => $salesOrder->status,
                'changed_by' => $user->login_ID,
                'remarks' => 'Sales Order edited',
            ]);
            });
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }

        $salesOrder->load(['items.product', 'customer', 'preparedBy']);

        return response()->json([
            'message' => 'Sales Order Updated Successfully',
            'sales_order' => $this->normalizeOrder($salesOrder),
        ]);
    }

    public function destroy(Request $request, SalesOrder $salesOrder): JsonResponse
    {
        if ($salesOrder->payment_status === 'Paid') {
            return response()->json(['message' => 'Paid orders cannot be deleted.'], 409);
        }

        if ($salesOrder->status === 'Confirmed') {
            return DB::transaction(function () use ($salesOrder, $request) {
                foreach ($salesOrder->items as $item) {
                    Product::query()->where('id', $item->product_id)->increment('qty', (float) $item->ordered_qty);
                }

                $salesOrder->status = 'Cancelled';
                $salesOrder->save();

                SalesOrderStatusLog::query()->create([
                    'sales_order_id' => $salesOrder->id,
                    'from_status' => 'Confirmed',
                    'to_status' => 'Cancelled',
                    'changed_by' => $request->user()->login_ID,
                    'remarks' => 'Sales Order cancelled',
                ]);

                return response()->json(['message' => 'Sales Order Cancelled Successfully']);
            });
        }

        DB::transaction(function () use ($salesOrder, $request) {
            SalesOrderStatusLog::query()->create([
                'sales_order_id' => $salesOrder->id,
                'from_status' => $salesOrder->status,
                'to_status' => 'Cancelled',
                'changed_by' => $request->user()->login_ID,
                'remarks' => 'Sales Order deleted',
            ]);

            $salesOrder->delete();
        });

        return response()->json(['message' => 'Sales Order Deleted Successfully']);
    }

    public function customers(Request $request): JsonResponse
    {
        $search = $request->query('search', []);
        $globalSearch = $request->query('q', '');
        $page = (int) $request->query('page', 1);

        $query = Customer::query()
            ->with(['priceReference', 'salesAgent', 'customerAddress'])
            ->orderBy('customer_name');

        if ($globalSearch !== '') {
            $query->where(function (Builder $nested) use ($globalSearch) {
                foreach (['customer_no', 'customer_name', 'tin', 'salesman_name'] as $column) {
                    $nested->orWhere($column, 'like', '%'.$globalSearch.'%');
                }
                $nested->orWhereHas('salesAgent', fn (Builder $a) => $a->where('name', 'like', '%'.$globalSearch.'%'));
            });
        }

        foreach ((array) $search as $column => $term) {
            $term = trim((string) $term);
            if ($term === '') continue;

            if ($column === 'customer_no') $query->where('customer_no', 'like', "%{$term}%");
            elseif ($column === 'customer_name') $query->where('customer_name', 'like', "%{$term}%");
            elseif ($column === 'tin') $query->where('tin', 'like', "%{$term}%");
            elseif ($column === 'salesman_name') $query->where('salesman_name', 'like', "%{$term}%");
            elseif ($column === 'price_reference') {
                $query->whereHas('priceReference', fn (Builder $r) => $r->where('code', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"));
            }
            elseif ($column === 'sales_agent') {
                $query->whereHas('salesAgent', fn (Builder $a) => $a->where('name', 'like', "%{$term}%"));
            }
        }

        $paginator = $query->paginate(50, ['*'], 'page', $page);

        return response()->json([
            'customers' => $paginator->getCollection()->map(fn (Customer $c) => [
                'id' => $c->id,
                'customer_no' => $c->customer_no,
                'customer_name' => $c->customer_name,
                'tin' => $c->tin,
                'price_reference' => strtolower((string) ($c->priceReference?->code ?? 'green')),
                'price_reference_label' => $c->priceReference?->name ?? 'Green',
                'discount_percent' => (float) $c->discount_percent,
                'sales_agent' => $c->salesAgent?->name,
                'salesman_name' => $c->salesman_name,
                'address' => $c->customerAddress?->formatted_address,
                'terms' => $c->terms,
            ]),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $search = $request->query('search', []);
        $globalSearch = $request->query('q', '');
        $page = (int) $request->query('page', 1);
        $selectedIds = $request->query('selected_ids', []);

        $query = Product::query()->orderBy('product');

        if ($globalSearch !== '') {
            $query->where(function (Builder $nested) use ($globalSearch) {
                foreach (['item_no', 'product', 'brand', 'unit'] as $column) {
                    $nested->orWhere($column, 'like', '%'.$globalSearch.'%');
                }
            });
        }

        foreach ((array) $search as $column => $term) {
            $term = trim((string) $term);
            if ($term === '') continue;

            if (in_array($column, ['item_no', 'product', 'brand', 'unit'], true)) {
                $query->where($column, 'like', "%{$term}%");
            }
        }

        $paginator = $query->paginate(50, ['*'], 'page', $page);

        return response()->json([
            'products' => $paginator->getCollection()->map(fn (Product $p) => [
                'id' => $p->id,
                'item_no' => $p->item_no,
                'product' => $p->product,
                'brand' => $p->brand,
                'unit' => $p->unit,
                'qty' => (float) $p->qty,
                'selling_price' => (float) $p->selling_price,
                'is_selected' => in_array($p->id, $selectedIds),
                'selectable' => (float) $p->qty > 0,
            ]),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    public function printSalesOrder(SalesOrder $salesOrder)
    {
        $salesOrder->load(['items.product', 'customer', 'preparedBy']);
        return view('admin.sales-order.print-sales-order', [
            'order' => $salesOrder,
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    public function printSalesInvoice(SalesOrder $salesOrder)
    {
        $salesOrder->load(['items', 'customer', 'preparedBy']);
        return view('admin.sales-order.print-sales-invoice', [
            'order' => $salesOrder,
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    public function printBoth(SalesOrder $salesOrder)
    {
        $salesOrder->load(['items', 'customer', 'preparedBy']);
        return view('admin.sales-order.print-both', [
            'order' => $salesOrder,
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    private function applyMainSearch(Builder $query, Request $request): void
    {
        $globalSearch = trim((string) $request->query('q', ''));
        if ($globalSearch !== '') {
            $query->where(function (Builder $nested) use ($globalSearch) {
                foreach (['so_no', 'customer_name_snapshot', 'customer_no_snapshot', 'tin_snapshot'] as $column) {
                    $nested->orWhere($column, 'like', '%'.$globalSearch.'%');
                }
            });
        }

        foreach ((array) $request->query('search', []) as $column => $term) {
            if (! in_array($column, $this->mainColumns, true)) continue;
            $term = trim((string) $term);
            if ($term === '') continue;
            $query->where($column, 'like', '%'.$term.'%');
        }
    }

    private function applySort(Builder $query, Request $request): void
    {
        $sort = $request->query('sort', '');
        $direction = $request->query('direction', '');

        if (in_array($sort, $this->sortAllowlist, true) && in_array($direction, ['asc', 'desc'], true)) {
            $query->orderBy($sort, $direction)->orderBy('id', $direction);
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }
    }

    private function customersJson(): string
    {
        return Customer::with(['priceReference', 'salesAgent', 'customerAddress'])
            ->orderBy('customer_name')
            ->get()
            ->map(fn (Customer $c) => [
                'id' => $c->id,
                'customer_no' => $c->customer_no,
                'customer_name' => $c->customer_name,
                'tin' => $c->tin,
                'price_reference' => strtolower((string) ($c->priceReference?->code ?? 'green')),
                'price_reference_label' => $c->priceReference?->name ?? 'Green',
                'discount_percent' => (float) $c->discount_percent,
                'sales_agent' => $c->salesAgent?->name,
                'salesman_name' => $c->salesman_name,
                'address' => $c->customerAddress?->formatted_address,
                'terms' => $c->terms,
            ])
            ->toJson();
    }

    private function productsJson(): string
    {
        return Product::query()->orderBy('product')
            ->get()
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'item_no' => $p->item_no,
                'product' => $p->product,
                'brand' => $p->brand,
                'unit' => $p->unit,
                'qty' => (float) $p->qty,
                'selling_price' => (float) $p->selling_price,
            ])
            ->toJson();
    }

    private function normalizeOrder(SalesOrder $order): array
    {
        return [
            'id' => $order->id,
            'so_no' => $order->so_no,
            'customer_id' => $order->customer_id,
            'customer_no_snapshot' => $order->customer_no_snapshot,
            'customer_name_snapshot' => $order->customer_name_snapshot,
            'tin_snapshot' => $order->tin_snapshot,
            'address_snapshot' => $order->address_snapshot,
            'price_reference_snapshot' => $order->price_reference_snapshot,
            'sales_agent_snapshot' => $order->sales_agent_snapshot,
            'salesman_snapshot' => $order->salesman_snapshot,
            'terms_snapshot' => $order->terms_snapshot,
            'sales_channel' => $order->sales_channel,
            'order_date' => $order->order_date?->toDateString(),
            'prepared_by_name_snapshot' => $order->prepared_by_name_snapshot,
            'payment_status' => $order->payment_status,
            'status' => $order->status,
            'total_ordered_qty' => (float) $order->total_ordered_qty,
            'total_without_vat' => (float) $order->total_without_vat,
            'vat_exclusive_total' => (float) $order->vat_exclusive_total,
            'vat_amount' => (float) $order->vat_amount,
            'total_with_vat' => (float) $order->total_with_vat,
            'confirmed_at' => $order->confirmed_at?->toDateTimeString(),
            'items' => $order->items?->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'item_no_snapshot' => $item->item_no_snapshot,
                'product_name_snapshot' => $item->product_name_snapshot,
                'brand_snapshot' => $item->brand_snapshot,
                'unit_snapshot' => $item->unit_snapshot,
                'available_qty' => (float) ($item->product?->qty ?? 0) + ($order->status === 'Confirmed' ? (float) $item->ordered_qty : 0),
                'ordered_qty' => (float) $item->ordered_qty,
                'selling_price_snapshot' => (float) $item->selling_price_snapshot,
                'discount_percent_snapshot' => (float) $item->discount_percent_snapshot,
                'unit_price_without_vat' => (float) $item->unit_price_without_vat,
                'line_total_without_vat' => (float) $item->line_total_without_vat,
                'vat_amount' => (float) $item->vat_amount,
                'line_total_with_vat' => (float) $item->line_total_with_vat,
            ]) ?? [],
        ];
    }
}
