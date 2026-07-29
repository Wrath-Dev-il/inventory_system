<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSalesQuotationRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SalesQuotation;
use App\Services\SalesQuotationNumberService;
use App\Services\SalesQuotationPricingService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesQuotationController extends Controller
{
    private SalesQuotationNumberService $numberService;
    private SalesQuotationPricingService $pricingService;

    private array $sortAllowlist = [
        'quotation_no', 'customer_name_snapshot', 'terms_snapshot',
        'remarks', 'quotation_date', 'created_at', 'status',
    ];

    private array $mainColumns = [
        'quotation_no', 'customer_name_snapshot', 'terms_snapshot',
        'remarks', 'quotation_date', 'status',
    ];

    public function __construct(
        SalesQuotationNumberService $numberService,
        SalesQuotationPricingService $pricingService
    ) {
        $this->numberService = $numberService;
        $this->pricingService = $pricingService;
    }

    public function index()
    {
        return view('admin.sales-order.sales-qoutation', [
            'title' => 'Sales Quotation',
            'subtitle' => 'Create, review, and manage Customer quotations.',
            'companyName' => config('company.name'),
            'storeUrl' => route('admin.sales-quotation.store'),
            'dataUrl' => route('admin.sales-quotation.data'),
            'customersUrl' => route('admin.sales-quotation.customers'),
            'productsUrl' => route('admin.sales-quotation.products'),
            'showUrlTemplate' => route('admin.sales-quotation.show', ['salesQuotation' => '__SALES_QUOTATION_ID__']),
            'printUrlTemplate' => route('admin.sales-quotation.print', ['salesQuotation' => '__SALES_QUOTATION_ID__']),
            'logoUrl' => asset('images/login/logo.png'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $query = SalesQuotation::query();

        $globalSearch = trim((string) $request->query('q', ''));
        if ($globalSearch !== '') {
            $query->where(function (Builder $nested) use ($globalSearch) {
                foreach (['quotation_no', 'customer_name_snapshot', 'remarks'] as $column) {
                    $nested->orWhere($column, 'like', '%' . $globalSearch . '%');
                }
            });
        }

        foreach ((array) $request->query('search', []) as $column => $term) {
            if (! in_array($column, $this->mainColumns, true)) continue;
            $term = trim((string) $term);
            if ($term === '') continue;
            $query->where($column, 'like', '%' . $term . '%');
        }

        $sort = $request->query('sort', '');
        $direction = $request->query('direction', '');
        if (in_array($sort, $this->sortAllowlist, true) && in_array($direction, ['asc', 'desc'], true)) {
            $query->orderBy($sort, $direction)->orderBy('id', $direction);
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $paginator = $query->paginate(25)->withQueryString();

        return response()->json([
            'quotations' => $paginator->getCollection()->map(fn (SalesQuotation $q) => [
                'id' => $q->id,
                'quotation_no' => $q->quotation_no,
                'customer_name_snapshot' => $q->customer_name_snapshot,
                'terms_snapshot' => $q->terms_snapshot,
                'remarks' => $q->remarks,
                'quotation_date' => $q->quotation_date?->toDateString(),
                'grand_total' => (float) $q->grand_total,
                'status' => $q->status,
                'created_at' => $q->created_at?->toDateTimeString(),
            ]),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
        ]);
    }

    public function customers(Request $request): JsonResponse
    {
        $search = $request->query('search', []);
        $globalSearch = $request->query('q', '');
        $page = (int) $request->query('page', 1);
        $sort = $request->query('sort', '');
        $direction = $request->query('direction', '');

        $query = Customer::query()
            ->with(['priceReference', 'customerAddress']);

        if ($globalSearch !== '') {
            $query->where(function (Builder $nested) use ($globalSearch) {
                foreach (['customer_no', 'customer_name', 'tin', 'salesman_name'] as $column) {
                    $nested->orWhere($column, 'like', '%' . $globalSearch . '%');
                }
            });
        }

        foreach ((array) $search as $column => $term) {
            $term = trim((string) $term);
            if ($term === '') continue;
            if ($column === 'customer_name') $query->where('customer_name', 'like', "%{$term}%");
            elseif ($column === 'price_reference') {
                $query->whereHas('priceReference', fn (Builder $r) => $r->where('code', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%"));
            }
            elseif ($column === 'terms') $query->where('terms', 'like', "%{$term}%");
            elseif ($column === 'tin') $query->where('tin', 'like', "%{$term}%");
            elseif ($column === 'address') {
                $query->whereHas('customerAddress', fn (Builder $a) => $a->where('formatted_address', 'like', "%{$term}%"));
            }
        }

        $sortAllowlist = ['customer_name', 'tin', 'terms'];
        if (in_array($sort, $sortAllowlist, true) && in_array($direction, ['asc', 'desc'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderByDesc('created_at')->orderByDesc('id');
        }

        $paginator = $query->paginate(25, ['*'], 'page', $page);

        return response()->json([
            'customers' => $paginator->getCollection()->map(fn (Customer $c) => [
                'id' => $c->id,
                'customer_no' => $c->customer_no,
                'customer_name' => $c->customer_name,
                'tin' => $c->tin,
                'price_reference' => strtolower((string) ($c->priceReference?->code ?? 'green')),
                'price_reference_label' => $c->priceReference?->name ?? 'Green',
                'terms' => $c->terms,
                'sales_agent' => $c->salesAgent?->name,
                'salesman_name' => $c->salesman_name,
                'address' => $c->customerAddress?->formatted_address,
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
        $sort = $request->query('sort', '');
        $direction = $request->query('direction', '');

        $query = Product::query();

        if ($globalSearch !== '') {
            $query->where(function (Builder $nested) use ($globalSearch) {
                foreach (['item_no', 'product', 'brand', 'unit'] as $column) {
                    $nested->orWhere($column, 'like', '%' . $globalSearch . '%');
                }
            });
        }

        foreach ((array) $search as $column => $term) {
            $term = trim((string) $term);
            if ($term === '') continue;
            if (in_array($column, ['product', 'brand', 'unit'], true)) {
                if ($column === 'product') $query->where('product', 'like', "%{$term}%");
                elseif ($column === 'brand') $query->where('brand', 'like', "%{$term}%");
                elseif ($column === 'unit') $query->where('unit', 'like', "%{$term}%");
            }
        }

        $sortAllowlist = ['product', 'brand', 'unit', 'selling_price'];
        if (in_array($sort, $sortAllowlist, true) && in_array($direction, ['asc', 'desc'], true)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('product');
        }

        $paginator = $query->paginate(25, ['*'], 'page', $page);

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
            ]),
            'total' => $paginator->total(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ]);
    }

    public function store(StoreSalesQuotationRequest $request): JsonResponse
    {
        $user = $request->user();

        $customer = Customer::with(['priceReference', 'customerAddress'])->findOrFail($request->customer_id);

        try {
            $quotation = DB::transaction(function () use ($request, $user, $customer) {
                $quotationNo = $this->numberService->generateQuotationNo();

                $itemRows = [];
                foreach ($request->items as $item) {
                    $product = Product::query()->lockForUpdate()->findOrFail($item['product_id']);
                    $itemRows[] = $this->pricingService->buildItemData(
                        [
                            'id' => $product->id,
                            'item_no' => $product->item_no,
                            'product' => $product->product,
                            'brand' => $product->brand,
                            'unit' => $product->unit,
                            'qty' => $product->qty,
                        ],
                        (float) $item['quantity'],
                        $item['offer_description'] ?? '',
                        (float) $item['unit_price'],
                        (float) ($item['discount_percent'] ?? 0),
                    );
                }

                $totals = $this->pricingService->calculateTotals($itemRows);

                $quotation = SalesQuotation::query()->create([
                    'quotation_no' => $quotationNo,
                    'quotation_date' => now()->toDateString(),
                    'customer_id' => $customer->id,
                    'customer_no_snapshot' => $customer->customer_no,
                    'customer_name_snapshot' => $customer->customer_name,
                    'price_reference_snapshot' => strtolower((string) ($customer->priceReference?->code ?? 'green')),
                    'terms_snapshot' => $customer->terms,
                    'tin_snapshot' => $customer->tin,
                    'address_snapshot' => $customer->customerAddress?->formatted_address,
                    'sales' => $request->sales,
                    'remarks' => $request->remarks,
                    'payment_terms' => $request->payment_terms,
                    'cancellation_terms' => $request->cancellation_terms,
                    'delivery_terms' => $request->delivery_terms,
                    'lead_time_at' => $request->lead_time_at,
                    'valid_until' => $request->valid_until,
                    'warranty' => $request->warranty,
                    'mode_of_payment' => $request->mode_of_payment,
                    'attention_to' => $request->attention_to,
                    'subtotal' => $totals['subtotal'],
                    'tax_amount' => $totals['tax_amount'],
                    'grand_total' => $totals['grand_total'],
                    'status' => 'confirmed',
                    'prepared_by_user_id' => $user->login_ID,
                    'prepared_by_name_snapshot' => $user->display_name ?: $user->User_ID,
                ]);

                foreach ($itemRows as $row) {
                    $quotation->items()->create($row);
                }

                return $quotation->fresh(['items', 'customer', 'preparedBy']);
            });

            return response()->json([
                'message' => 'Sales Quotation Created Successfully',
                'quotation' => $this->normalizeQuotation($quotation),
                'print_url' => route('admin.sales-quotation.print', ['salesQuotation' => $quotation->id]),
            ], 201);

        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 409);
        }
    }

    public function show(SalesQuotation $salesQuotation): JsonResponse
    {
        $salesQuotation->load(['items.product', 'customer', 'preparedBy']);
        return response()->json(['quotation' => $this->normalizeQuotation($salesQuotation)]);
    }

    public function print(SalesQuotation $salesQuotation)
    {
        $salesQuotation->load(['items.product', 'customer', 'preparedBy']);

        if ($salesQuotation->items->isEmpty()) {
            return view('admin.sales-order.print-error');
        }

        return view('admin.sales-order.print-sales-quotation', [
            'quotation' => $salesQuotation,
            'companyName' => config('company.name'),
            'logoUrl' => asset(config('company.logo')),
        ]);
    }

    private function normalizeQuotation(SalesQuotation $quotation): array
    {
        return [
            'id' => $quotation->id,
            'quotation_no' => $quotation->quotation_no,
            'quotation_date' => $quotation->quotation_date?->toDateString(),
            'customer_id' => $quotation->customer_id,
            'customer_no_snapshot' => $quotation->customer_no_snapshot,
            'customer_name_snapshot' => $quotation->customer_name_snapshot,
            'price_reference_snapshot' => $quotation->price_reference_snapshot,
            'terms_snapshot' => $quotation->terms_snapshot,
            'tin_snapshot' => $quotation->tin_snapshot,
            'address_snapshot' => $quotation->address_snapshot,
            'sales' => $quotation->sales,
            'remarks' => $quotation->remarks,
            'payment_terms' => $quotation->payment_terms,
            'cancellation_terms' => $quotation->cancellation_terms,
            'delivery_terms' => $quotation->delivery_terms,
            'lead_time_at' => $quotation->lead_time_at?->toDateTimeString(),
            'valid_until' => $quotation->valid_until?->toDateTimeString(),
            'warranty' => $quotation->warranty,
            'mode_of_payment' => $quotation->mode_of_payment,
            'attention_to' => $quotation->attention_to,
            'subtotal' => (float) $quotation->subtotal,
            'tax_amount' => (float) $quotation->tax_amount,
            'grand_total' => (float) $quotation->grand_total,
            'status' => $quotation->status,
            'prepared_by_name_snapshot' => $quotation->prepared_by_name_snapshot,
            'created_at' => $quotation->created_at?->toDateTimeString(),
            'items' => $quotation->items?->map(fn ($item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'item_no_snapshot' => $item->item_no_snapshot,
                'item_name_snapshot' => $item->item_name_snapshot,
                'brand_snapshot' => $item->brand_snapshot,
                'unit_snapshot' => $item->unit_snapshot,
                'available_quantity_snapshot' => (float) $item->available_quantity_snapshot,
                'quantity' => (float) $item->quantity,
                'offer_description' => $item->offer_description,
                'discount_percent' => (float) ($item->discount_percent ?? 0),
                'unit_price_without_tax_snapshot' => (float) ($item->unit_price_without_tax_snapshot ?? 0),
                'unit_price' => (float) $item->unit_price,
                'unit_price_with_tax' => (float) $item->unit_price_with_tax,
                'tax_amount' => (float) $item->tax_amount,
                'line_total' => (float) $item->line_total,
            ]) ?? [],
        ];
    }
}
