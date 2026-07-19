<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    private int $defaultLowStockThreshold = 10;

    private int $perPage = 10;

    public function index(Request $request)
    {
        $schema = $this->inventorySchema();
        $product = $schema['product'];
        $supplier = $schema['supplier'];
        $customer = $schema['customer'];

        $totalProducts = $product ? DB::table($product['table'])->count() : 0;
        $totalSuppliers = $supplier ? DB::table($supplier['table'])->count() : 0;
        $totalCustomers = $customer ? DB::table($customer['table'])->count() : 0;
        $lowStockCount = $product ? $this->lowStockQuery($product)->count() : 0;

        return view('admin.dashboard', [
            'adminName' => Auth::user()?->display_name,
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
            'totalProducts' => $totalProducts,
            'lowStockCount' => $lowStockCount,
            'totalSuppliers' => $totalSuppliers,
            'totalCustomers' => $totalCustomers,
            'highStockProducts' => $product ? $this->stockPaginator($product, 'high', $request) : null,
            'lowStockProducts' => $product ? $this->stockPaginator($product, 'low', $request) : null,
            'qtyDistribution' => $product ? $this->qtyDistribution($product) : $this->emptyQtyDistribution(),
            'stockRuleLabel' => $this->stockRuleLabel($product),
            'schemaNotes' => $this->schemaNotes($schema),
            'stockFilters' => $this->stockFilters($request),
            'activeStockTab' => $request->query('active_stock_tab') === 'low' ? 'low' : 'high',
        ]);
    }

    private function inventorySchema(): array
    {
        return [
            'product' => $this->productSchema(),
            'supplier' => $this->firstExistingTable(['suppliers', 'supplier', 'vendors', 'vendor_master']),
            'customer' => $this->firstExistingTable(['customers', 'customer', 'clients', 'client_master']),
        ];
    }

    private function productSchema(): ?array
    {
        $table = $this->firstExistingTable(['products', 'product_master', 'items', 'inventory_items', 'stocks']);

        if (! $table) {
            return null;
        }

        $columns = Schema::getColumnListing($table['table']);
        $quantityColumn = $this->firstColumn($columns, [
            'available_qty',
            'Available_QTY',
            'available_quantity',
            'quantity',
            'qty',
            'stock',
            'current_stock',
            'on_hand',
        ]);

        if (! $quantityColumn) {
            return null;
        }

        return [
            'table' => $table['table'],
            'columns' => $columns,
            'name_column' => $this->firstColumn($columns, [
                'product',
                'Product',
                'product_name',
                'Product_Name',
                'item_name',
                'Item_Name',
                'name',
                'Name',
                'description',
            ]),
            'unit_column' => $this->firstColumn($columns, [
                'unit',
                'Unit',
                'product_unit',
                'Product_Unit',
                'uom',
                'UOM',
            ]),
            'brand_column' => $this->firstColumn($columns, [
                'brand',
                'Brand',
                'brand_name',
                'Brand_Name',
                'manufacturer',
            ]),
            'quantity_column' => $quantityColumn,
            'threshold_column' => $this->firstColumn($columns, [
                'restock_level',
                'Restock_Level',
                'minimum_stock',
                'Minimum_Stock',
                'reorder_level',
                'Reorder_Level',
                'reorder_point',
                'low_stock_level',
                'stock_limit',
            ]),
        ];
    }

    private function firstExistingTable(array $candidates): ?array
    {
        foreach ($candidates as $candidate) {
            if (Schema::hasTable($candidate)) {
                return ['table' => $candidate];
            }
        }

        return null;
    }

    private function firstColumn(array $columns, array $candidates): ?string
    {
        $lookup = collect($columns)->mapWithKeys(
            fn (string $column) => [strtolower($column) => $column]
        );

        foreach ($candidates as $candidate) {
            $column = $lookup->get(strtolower($candidate));

            if ($column) {
                return $column;
            }
        }

        return null;
    }

    private function stockPaginator(array $product, string $type, Request $request)
    {
        $query = $type === 'low'
            ? $this->lowStockQuery($product)
            : $this->highStockQuery($product);

        $this->applyStockFilters($query, $product, $this->stockFilters($request)[$type]);

        $quantityColumn = $this->qualifiedWrapped($product['table'], $product['quantity_column']);
        $nameColumn = $product['name_column'];

        $query->orderByRaw($quantityColumn.' '.($type === 'low' ? 'asc' : 'desc'));

        if ($nameColumn) {
            $query->orderBy($product['table'].'.'.$nameColumn);
        }

        return $query
            ->paginate($this->perPage, ['*'], $type.'_page')
            ->withQueryString();
    }

    private function highStockQuery(array $product): Builder
    {
        $query = $this->productBaseQuery($product);

        if ($product['threshold_column']) {
            return $query->whereRaw(
                $this->qualifiedWrapped($product['table'], $product['quantity_column']).
                ' > COALESCE('.$this->qualifiedWrapped($product['table'], $product['threshold_column']).', ?)',
                [$this->defaultLowStockThreshold]
            );
        }

        return $query->where($product['table'].'.'.$product['quantity_column'], '>', $this->defaultLowStockThreshold);
    }

    private function lowStockQuery(array $product): Builder
    {
        $query = $this->productBaseQuery($product);

        if ($product['threshold_column']) {
            return $query->whereRaw(
                $this->qualifiedWrapped($product['table'], $product['quantity_column']).
                ' <= COALESCE('.$this->qualifiedWrapped($product['table'], $product['threshold_column']).', ?)',
                [$this->defaultLowStockThreshold]
            );
        }

        return $query->where($product['table'].'.'.$product['quantity_column'], '<=', $this->defaultLowStockThreshold);
    }

    private function productBaseQuery(array $product): Builder
    {
        $nameColumn = $product['name_column'];
        $unitColumn = $product['unit_column'];
        $quantityColumn = $product['quantity_column'];

        return DB::table($product['table'])
            ->selectRaw(
                ($nameColumn ? $this->qualifiedWrapped($product['table'], $nameColumn) : "'Unnamed product'").' as product, '.
                ($unitColumn ? $this->qualifiedWrapped($product['table'], $unitColumn) : "'N/A'").' as unit, '.
                $this->qualifiedWrapped($product['table'], $quantityColumn).' as available_qty'
            );
    }

    private function stockFilters(Request $request): array
    {
        return [
            'high' => [
                'product' => trim((string) $request->query('high_product', '')),
                'unit' => trim((string) $request->query('high_unit', '')),
                'qty' => trim((string) $request->query('high_qty', '')),
            ],
            'low' => [
                'product' => trim((string) $request->query('low_product', '')),
                'unit' => trim((string) $request->query('low_unit', '')),
                'qty' => trim((string) $request->query('low_qty', '')),
            ],
        ];
    }

    private function applyStockFilters(Builder $query, array $product, array $filters): void
    {
        if ($filters['product'] !== '' && $product['name_column']) {
            $query->where($product['table'].'.'.$product['name_column'], 'like', '%'.$filters['product'].'%');
        }

        if ($filters['unit'] !== '' && $product['unit_column']) {
            $query->where($product['table'].'.'.$product['unit_column'], 'like', '%'.$filters['unit'].'%');
        }

        if ($filters['qty'] !== '') {
            $query->whereRaw(
                'CAST('.$this->qualifiedWrapped($product['table'], $product['quantity_column']).' AS CHAR) LIKE ?',
                ['%'.$filters['qty'].'%']
            );
        }
    }

    private function qtyDistribution(array $product): array
    {
        if (! $product['brand_column']) {
            return $this->emptyQtyDistribution();
        }

        $brandExpression = "COALESCE(NULLIF(TRIM(".$this->qualifiedWrapped($product['table'], $product['brand_column'])."), ''), 'Unknown')";
        $qtyExpression = $this->qualifiedWrapped($product['table'], $product['quantity_column']);

        $normalizedBrandStock = DB::table($product['table'])
            ->selectRaw($brandExpression.' as brand, '.$qtyExpression.' as available_qty');

        $rows = DB::query()
            ->fromSub($normalizedBrandStock, 'brand_stock')
            ->selectRaw('brand, SUM(available_qty) as available_qty')
            ->groupBy('brand')
            ->havingRaw('SUM(available_qty) > 0')
            ->orderByDesc('available_qty')
            ->get();

        return [
            'labels' => $rows->pluck('brand')->values()->all(),
            'values' => $rows->map(fn ($row) => (float) $row->available_qty)->values()->all(),
            'total' => (float) $rows->sum('available_qty'),
        ];
    }

    private function emptyQtyDistribution(): array
    {
        return [
            'labels' => [],
            'values' => [],
            'total' => 0,
        ];
    }

    private function stockRuleLabel(?array $product): string
    {
        if (! $product) {
            return 'No product table found in the inventory_system database.';
        }

        if ($product['threshold_column']) {
            return 'Low stock uses '.$product['quantity_column'].' <= '.$product['threshold_column'].'.';
        }

        return 'Low stock uses '.$product['quantity_column'].' <= '.$this->defaultLowStockThreshold.'.';
    }

    private function schemaNotes(array $schema): array
    {
        $notes = [];

        if (! $schema['product']) {
            $notes[] = 'No product table was found, so product cards, tables, and chart data are shown as empty states.';
        }

        if (! $schema['supplier']) {
            $notes[] = 'No supplier table was found, so Total Supplier is 0.';
        }

        if (! $schema['customer']) {
            $notes[] = 'No customer table was found, so Total Customer is 0.';
        }

        return $notes;
    }

    private function wrapped(string $column): string
    {
        return DB::getQueryGrammar()->wrap($column);
    }

    private function qualifiedWrapped(string $table, string $column): string
    {
        return $this->wrapped($table.'.'.$column);
    }
}
