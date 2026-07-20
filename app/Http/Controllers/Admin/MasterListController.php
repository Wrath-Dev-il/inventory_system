<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemSource;
use App\Models\ItemSourceEquivalency;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class MasterListController extends Controller
{
    private const NEW_ITEM_DAYS = 30;

    private array $productColumns = [
        'item_no',
        'product',
        'brand',
        'unit',
        'qty',
        'restock_level',
        'item_source',
        'cost_in_yuan',
        'cost_in_peso',
        'selling_price',
        'price_online',
    ];

    private array $editableColumns = [
        'product',
        'brand',
        'unit',
        'qty',
        'restock_level',
        'item_source',
        'item_source_id',
        'cost_in_yuan',
        'cost_in_peso',
        'selling_price',
        'price_online',
    ];

    public function products(Request $request)
    {
        $tableExists = Schema::hasTable('products');
        $products = $tableExists ? $this->paginatedProducts($request) : null;
        $stats = $tableExists ? $this->productStats() : $this->emptyStats();

        return view('admin.Product-List.Product-List', [
            'title' => 'Product List',
            'subtitle' => 'Manage product inventory, stock levels and pricing.',
            'tableName' => 'products',
            'tableExists' => $tableExists,
            'products' => $products,
            'stats' => $stats,
            'filters' => $this->filters(),
            'activeFilter' => $request->query('filter', 'all'),
            'searches' => $request->query('search', []),
            'globalSearch' => $request->query('q', ''),
            'nextItemNo' => $tableExists ? $this->nextItemNo() : 'ITEM-000001',
            'newItemDays' => self::NEW_ITEM_DAYS,
            'productDetailsUrlTemplate' => route('admin.products.show', ['product' => '__PRODUCT_ID__']),
            'productStoreUrl' => route('admin.products.store'),
            'productUpdateUrl' => route('admin.products.bulk-update'),
            'productDestroyUrlTemplate' => route('admin.products.destroy', ['product' => '__PRODUCT_ID__']),
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
            'itemSources' => $tableExists ? ItemSource::with('currentEquivalency')->orderBy('name')->get(['id', 'name']) : collect(),
        ]);
    }

    public function storeProduct(Request $request): JsonResponse|RedirectResponse
    {
        $payload = $this->validatedProductPayload($request);
        $product = DB::transaction(function () use ($payload) {
            $product = new Product($this->productAttributes($payload));
            $product->item_no = $this->nextItemNo(true);
            $product->save();
            return $product->fresh();
        });

        return $this->productMutationResponse($request, 'Product saved successfully.', [$product]);
    }

    public function product(Product $product): JsonResponse
    {
        return response()->json([
            'product' => $this->normalizeProduct($product),
        ]);
    }

    public function destroyProduct(Request $request, Product $product): JsonResponse
    {
        abort_unless((int) $request->user()?->account_type === 1, 403);

        $deletedProduct = $this->normalizeProduct($product);

        if ($this->productHasBlockingReferences($product)) {
            return response()->json([
                'blocked' => true,
                'message' => 'This product is already used by another record, so it cannot be deleted. Keep it in the product list to preserve inventory and sales history.',
                'product' => $deletedProduct,
            ]);
        }

        try {
            DB::transaction(function () use ($product) {
                $product->delete();
            });
        } catch (QueryException) {
            return response()->json([
                'blocked' => true,
                'message' => 'This product is already used by another record, so it cannot be deleted. Keep it in the product list to preserve inventory and sales history.',
                'product' => $deletedProduct,
            ]);
        }

        return response()->json([
            'deleted' => true,
            'message' => 'Product deleted successfully.',
            'product' => $deletedProduct,
            'stats' => $this->productStats(),
            'next_item_no' => $this->nextItemNo(),
        ]);
    }

    public function bulkUpdateProducts(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'edits' => ['required', 'array', 'min:1'],
            'edits.*.id' => ['required', 'integer', 'exists:products,id'],
            'edits.*.field' => ['required', 'string', Rule::in($this->editableColumns)],
            'edits.*.value' => ['nullable'],
        ]);

        $updated = DB::transaction(function () use ($validated) {
            $products = collect();

            foreach ($validated['edits'] as $edit) {
                $product = Product::query()->lockForUpdate()->findOrFail($edit['id']);
                $this->applyInlineEdit($product, $edit['field'], $edit['value']);
                $product->save();
                $products->put($product->id, $product->fresh());
            }

            return $products->values();
        });

        return response()->json([
            'message' => 'Product changes saved successfully.',
            'products' => $updated->map(fn (Product $product) => $this->normalizeProduct($product))->values(),
            'stats' => $this->productStats(),
        ]);
    }

    public function suppliers()
    {
        return $this->show('Supplier List', 'suppliers');
    }

    public function customers()
    {
        return $this->show('Customer List', 'customers');
    }

    private function show(string $title, string $table)
    {
        return view('admin.master-list', [
            'title' => $title,
            'tableName' => $table,
            'tableExists' => Schema::hasTable($table),
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    private function paginatedProducts(Request $request): LengthAwarePaginator
    {
        $query = Product::query()->orderByDesc('created_at')->orderByDesc('id');
        $this->applyProductFilters($query, $request);

        $paginator = $query->paginate(50)->withQueryString();
        $paginator->getCollection()->transform(fn (Product $product) => $this->normalizeProduct($product));

        return $paginator;
    }

    private function applyProductFilters(Builder $query, Request $request): void
    {
        $globalSearch = trim((string) $request->query('q', ''));

        if ($globalSearch !== '') {
            $query->where(function (Builder $nested) use ($globalSearch) {
                foreach ($this->productColumns as $column) {
                    $nested->orWhere($column, 'like', '%'.$globalSearch.'%');
                }
            });
        }

        foreach ((array) $request->query('search', []) as $column => $term) {
            if (! in_array($column, $this->productColumns, true)) {
                continue;
            }

            $term = trim((string) $term);

            if ($term === '') {
                continue;
            }

            $query->where($column, 'like', '%'.$term.'%');
        }

        match ($request->query('filter', 'all')) {
            'new' => $query->where('created_at', '>=', now()->subDays(self::NEW_ITEM_DAYS)),
            'old' => $query->where('created_at', '<', now()->subDays(self::NEW_ITEM_DAYS)),
            'low' => $query->whereColumn('qty', '<=', 'restock_level'),
            'high' => $query->whereRaw('qty > restock_level + GREATEST(1, CEIL(restock_level * 0.25))'),
            default => null,
        };
    }

    private function filters(): array
    {
        return [
            'all' => 'All Items',
            'new' => 'Newly Added Items',
            'old' => 'Old Items',
            'low' => 'Low Stock',
            'high' => 'High Stock',
        ];
    }

    private function productStats(): array
    {
        $products = Product::query()->get();
        $normalized = $products->map(fn (Product $product) => $this->normalizeProduct($product));
        $costValues = $normalized->pluck('cost_in_peso')->filter(fn ($value) => $value !== null);
        $profitValues = $normalized->pluck('estimated_profit')->filter(fn ($value) => $value !== null);

        return [
            'total_products' => $products->count(),
            'high_stocks' => $normalized->where('stock_status.tone', 'high')->count(),
            'low_stocks' => $normalized->where('stock_status.tone', 'low')->count(),
            'average_cost' => $costValues->count() ? $costValues->avg() : null,
            'average_gross_profit' => $profitValues->count() ? $profitValues->avg() : null,
        ];
    }

    private function emptyStats(): array
    {
        return [
            'total_products' => 0,
            'high_stocks' => 0,
            'low_stocks' => 0,
            'average_cost' => null,
            'average_gross_profit' => null,
        ];
    }

    private function validatedProductPayload(Request $request): array
    {
        return $request->validate([
            'product' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'unit' => ['nullable', 'string', 'max:80'],
            'qty' => ['required', 'numeric', 'min:0'],
            'restock_level' => ['required', 'numeric', 'min:0'],
            'item_source' => ['nullable', 'string', 'max:255'],
            'item_source_id' => ['nullable', 'integer', 'exists:item_sources,id'],
            'cost_currency' => ['nullable', Rule::in(['PHP', 'CNY'])],
            'cost_value' => ['nullable', 'numeric', 'min:0'],
            'cost_in_yuan' => ['nullable', 'numeric', 'min:0'],
            'cost_in_peso' => ['nullable', 'numeric', 'min:0'],
            'cost_input_mode' => ['nullable', Rule::in(['yuan', 'peso'])],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'price_online' => ['nullable', 'numeric', 'min:0'],
            'sea_freight' => ['nullable', 'numeric', 'min:0'],
            'air_freight' => ['nullable', 'numeric', 'min:0'],
        ]);
    }

    private function productAttributes(array $payload): array
    {
        $costYuan = $this->numericValue($payload['cost_in_yuan'] ?? null);
        $costPeso = $this->numericValue($payload['cost_in_peso'] ?? null);
        $costInputMode = $payload['cost_input_mode'] ?? null;
        $itemSourceId = $payload['item_source_id'] ?? null;

        if ($itemSourceId && $costInputMode) {
            $multiplier = $this->loadMultiplier($itemSourceId);

            if ($multiplier !== null && $multiplier > 0) {
                if ($costInputMode === 'yuan' && $costYuan !== null) {
                    $costPeso = round($costYuan * $multiplier, 2);
                } elseif ($costInputMode === 'peso' && $costPeso !== null) {
                    $costYuan = round($costPeso / $multiplier, 2);
                    $costPeso = round($costYuan * $multiplier, 2);
                }
            }
        }

        $itemSourceText = $payload['item_source'] ?? null;
        if ($itemSourceId && ! $itemSourceText) {
            $source = ItemSource::find($itemSourceId);
            $itemSourceText = $source?->name;
        }

        return [
            'product' => $payload['product'],
            'brand' => $payload['brand'] ?? null,
            'unit' => $payload['unit'] ?? null,
            'qty' => $this->numericValue($payload['qty']) ?? 0,
            'restock_level' => $this->numericValue($payload['restock_level']) ?? 0,
            'item_source' => $itemSourceText,
            'item_source_id' => $itemSourceId,
            'cost_currency' => $costPeso !== null ? 'PHP' : ($costYuan !== null ? 'CNY' : 'PHP'),
            'cost_value' => $costPeso ?? $costYuan ?? 0,
            'cost_in_yuan' => $costYuan,
            'cost_in_peso' => $costPeso,
            'selling_price' => $this->numericValue($payload['selling_price']) ?? 0,
            'price_online' => $this->numericValue($payload['price_online'] ?? null),
            'sea_freight' => $this->numericValue($payload['sea_freight'] ?? null) ?? 0,
            'air_freight' => $this->numericValue($payload['air_freight'] ?? null) ?? 0,
        ];
    }

    private function loadMultiplier(int $itemSourceId): ?float
    {
        $equiv = ItemSourceEquivalency::where('item_source_id', $itemSourceId)
            ->whereNotNull('multiplier')
            ->where('multiplier', '>', 0)
            ->latest('id')
            ->value('multiplier');

        return $equiv !== null ? (float) $equiv : null;
    }

    private function applyInlineEdit(Product $product, string $field, mixed $value): void
    {
        if (in_array($field, ['brand', 'unit', 'item_source'], true)) {
            $product->{$field} = trim((string) $value) !== '' ? $value : null;
            return;
        }

        if ($field === 'item_source_id') {
            $product->item_source_id = $value !== null && $value !== '' ? (int) $value : null;
            if ($product->item_source_id) {
                $source = ItemSource::find($product->item_source_id);
                $product->item_source = $source?->name;
            }
            return;
        }

        if ($field === 'price_online' && ($value === null || $value === '')) {
            $product->price_online = null;
            return;
        }

        if (in_array($field, ['qty', 'restock_level', 'cost_in_yuan', 'cost_in_peso', 'selling_price', 'price_online'], true)) {
            $value = $this->numericValue($value) ?? 0;
        }

        if ($field === 'cost_in_yuan') {
            $product->cost_currency = 'CNY';
            $product->cost_value = $value;
            $product->cost_in_yuan = $value;
            $multiplier = $product->item_source_id ? $this->loadMultiplier($product->item_source_id) : null;
            if ($multiplier !== null && $multiplier > 0 && $value > 0) {
                $product->cost_in_peso = round($value * $multiplier, 2);
            } else {
                $product->cost_in_peso = null;
            }
            return;
        }

        if ($field === 'cost_in_peso') {
            $product->cost_currency = 'PHP';
            $product->cost_value = $value;
            $product->cost_in_peso = $value;
            $multiplier = $product->item_source_id ? $this->loadMultiplier($product->item_source_id) : null;
            if ($multiplier !== null && $multiplier > 0 && $value > 0) {
                $product->cost_in_yuan = round($value / $multiplier, 2);
            } else {
                $product->cost_in_yuan = null;
            }
            return;
        }

        $product->{$field} = $value;
    }

    private function productMutationResponse(Request $request, string $message, array|Collection $products): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return redirect()->route('admin.products.index')->with('status', $message);
        }

        return response()->json([
            'message' => $message,
            'products' => collect($products)->map(fn (Product $product) => $this->normalizeProduct($product))->values(),
            'stats' => $this->productStats(),
            'next_item_no' => $this->nextItemNo(),
        ], 201);
    }

    private function productHasBlockingReferences(Product $product): bool
    {
        $connection = DB::connection();

        if ($connection->getDriverName() !== 'mysql') {
            return false;
        }

        $database = $connection->getDatabaseName();
        $references = collect(DB::select(
            "SELECT TABLE_NAME, COLUMN_NAME
             FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = ?
               AND REFERENCED_TABLE_NAME = 'products'
               AND REFERENCED_COLUMN_NAME = 'id'",
            [$database]
        ));

        $productIdColumns = collect(DB::select(
            "SELECT TABLE_NAME, COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME <> 'products'
               AND COLUMN_NAME = 'product_id'",
            [$database]
        ));

        return $references
            ->merge($productIdColumns)
            ->unique(fn ($reference) => $reference->TABLE_NAME.'.'.$reference->COLUMN_NAME)
            ->contains(function ($reference) use ($product) {
                return Schema::hasTable($reference->TABLE_NAME)
                    && Schema::hasColumn($reference->TABLE_NAME, $reference->COLUMN_NAME)
                    && DB::table($reference->TABLE_NAME)->where($reference->COLUMN_NAME, $product->id)->exists();
            });
    }

    private function nextItemNo(bool $lock = false): string
    {
        $query = Product::query();

        if ($lock) {
            $query->lockForUpdate();
        }

        $last = $query
            ->where('item_no', 'like', 'ITEM-%')
            ->orderByDesc('id')
            ->value('item_no');

        if (! $last || ! preg_match('/ITEM-(\d+)/', $last, $matches)) {
            return 'ITEM-000001';
        }

        return 'ITEM-'.str_pad((string) (((int) $matches[1]) + 1), 6, '0', STR_PAD_LEFT);
    }

    private function normalizeProduct(Product $product): array
    {
        $costPeso = $this->numericValue($product->cost_in_peso);
        $sellingPrice = $this->numericValue($product->selling_price);
        $seaFreight = $this->numericValue($product->sea_freight) ?? 0;
        $airFreight = $this->numericValue($product->air_freight) ?? 0;
        $totalCost = $costPeso !== null ? $costPeso + $seaFreight + $airFreight : null;
        $estimatedProfit = $totalCost !== null && $sellingPrice !== null ? $sellingPrice - $totalCost : null;
        $markup = $totalCost !== null && $totalCost != 0.0 && $estimatedProfit !== null
            ? ($estimatedProfit / $totalCost) * 100
            : null;

        return [
            'id' => $product->id,
            'item_no' => $product->item_no,
            'product' => $product->product,
            'brand' => $product->brand,
            'unit' => $product->unit,
            'qty' => $this->numericValue($product->qty),
            'restock_level' => $this->numericValue($product->restock_level),
            'item_source' => $product->item_source,
            'item_source_id' => $product->item_source_id,
            'cost_currency' => $product->cost_currency,
            'cost_value' => $this->numericValue($product->cost_value),
            'cost_yuan' => $this->numericValue($product->cost_in_yuan),
            'cost_peso' => $costPeso,
            'cost_in_yuan' => $this->numericValue($product->cost_in_yuan),
            'cost_in_peso' => $costPeso,
            'selling_price' => $sellingPrice,
            'price_online' => $this->numericValue($product->price_online),
            'sea_freight' => $seaFreight,
            'air_freight' => $airFreight,
            'total_cost' => $totalCost,
            'item_selling_price' => $sellingPrice,
            'estimated_profit' => $estimatedProfit,
            'markup' => $markup,
            'markup_unavailable_reason' => $totalCost === 0.0 ? 'zero_total_cost' : null,
            'stock_status' => $this->stockStatus($this->numericValue($product->qty), $this->numericValue($product->restock_level)),
            'created_at' => $product->created_at?->toDateTimeString(),
        ];
    }

    private function numericValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function stockStatus(?float $qty, ?float $restockLevel): array
    {
        if ($qty === null || $restockLevel === null) {
            return ['label' => 'Unknown', 'tone' => 'unknown'];
        }

        if ($qty <= $restockLevel) {
            return ['label' => 'Low Stock', 'tone' => 'low'];
        }

        $nearLowThreshold = $restockLevel + max(1, ceil($restockLevel * 0.25));

        if ($qty <= $nearLowThreshold) {
            return ['label' => 'Near Low Stock', 'tone' => 'near'];
        }

        return ['label' => 'High Stock', 'tone' => 'high'];
    }
}
