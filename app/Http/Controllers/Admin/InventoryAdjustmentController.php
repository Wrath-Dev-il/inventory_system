<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InventoryAdjustmentController extends Controller
{
    private array $columns = [
        'item_no', 'product', 'brand', 'unit', 'restock_level', 'qty',
    ];

    public function index()
    {
        $tableExists = Schema::hasTable('products');
        $stats = $tableExists ? $this->stats() : $this->emptyStats();

        return view('admin.inventory-management.inventory-adjustment', [
            'stats' => $stats,
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $tab = $request->query('tab', 'all');
        $search = $request->query('search', []);
        $sortField = $request->query('sort', 'product');
        $sortDir = $request->query('dir', 'asc');
        $perPage = min((int) $request->query('per_page', 25), 100);

        if (!in_array($sortField, $this->columns, true)) {
            $sortField = 'product';
        }
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';

        $query = Product::query();

        $query->where(function ($q) use ($search) {
            foreach ((array) $search as $column => $term) {
                if (!in_array($column, $this->columns, true)) continue;
                $term = trim((string) $term);
                if ($term === '') continue;
                $q->where($column, 'like', '%' . $term . '%');
            }
        });

        match ($tab) {
            'high' => $query->whereRaw('COALESCE(qty, 0) > COALESCE(restock_level, 0) + GREATEST(1, CEIL(COALESCE(restock_level, 0) * 0.25))'),
            'low' => $query->whereColumn('qty', '<=', 'restock_level'),
            default => null,
        };

        $paginator = $query->orderBy($sortField, $sortDir)->paginate($perPage)->withQueryString();

        return response()->json([
            'products' => collect($paginator->items())->map(fn ($p) => $this->normalize($p)),
            'stats' => $this->stats(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
                'links' => $paginator->linkCollection()->toArray(),
            ],
        ]);
    }

    public function save(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'adjustments' => ['required', 'array', 'min:1'],
            'adjustments.*.id' => ['required', 'integer', 'exists:products,id'],
            'adjustments.*.original_qty' => ['required', 'numeric', 'min:0'],
            'adjustments.*.new_qty' => ['required', 'integer', 'min:0'],
        ]);

        $user = $request->user();
        $adjustedBy = $user ? $user->login_ID : null;

        try {
            $result = DB::transaction(function () use ($validated, $adjustedBy) {
                $updatedProducts = collect();
                $conflicts = [];

                foreach ($validated['adjustments'] as $adj) {
                    $product = Product::query()->lockForUpdate()->findOrFail($adj['id']);

                    $currentQty = (float) ($product->qty ?? 0);
                    $originalQty = (float) $adj['original_qty'];
                    $newQty = (float) $adj['new_qty'];

                    if (abs($currentQty - $originalQty) > 0.001) {
                        $conflicts[] = [
                            'id' => $product->id,
                            'item_no' => $product->item_no,
                            'product' => $product->product,
                            'current_qty' => $currentQty,
                            'expected_qty' => $originalQty,
                        ];
                        continue;
                    }

                    if (abs($currentQty - $newQty) < 0.001) {
                        continue;
                    }

                    $difference = $newQty - $currentQty;

                    $product->qty = $newQty;
                    $product->save();

                    InventoryAdjustment::create([
                        'product_id' => $product->id,
                        'previous_qty' => $currentQty,
                        'new_qty' => $newQty,
                        'difference' => $difference,
                        'adjustment_type' => 'manual',
                        'reason' => 'Inventory adjustment',
                        'adjusted_by' => $adjustedBy,
                    ]);

                    $updatedProducts->push($product->fresh());
                }

                if (!empty($conflicts)) {
                    throw new \App\Exceptions\InventoryConflictException($conflicts);
                }

                return response()->json([
                    'message' => count($updatedProducts) . ' inventory items updated successfully.',
                    'products' => $updatedProducts->map(fn ($p) => $this->normalize($p))->values(),
                    'stats' => $this->stats(),
                ]);
            });

            return $result;
        } catch (\App\Exceptions\InventoryConflictException $e) {
            return response()->json([
                'message' => 'Some inventory quantities changed before your adjustment was saved. Refresh the affected items and try again.',
                'conflicts' => $e->getConflicts(),
            ], 409);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to save adjustments. Please try again.',
            ], 500);
        }
    }

    private function stats(): array
    {
        $all = Product::query()->get(['qty', 'restock_level']);

        $total = $all->count();
        $high = $all->filter(fn ($p) => ($p->qty ?? 0) > ($p->restock_level ?? 0) + max(1, ceil(($p->restock_level ?? 0) * 0.25)))->count();
        $low = $all->filter(fn ($p) => ($p->qty ?? 0) <= ($p->restock_level ?? 0))->count();

        return [
            'total_products' => $total,
            'high_stocks' => $high,
            'low_stocks' => $low,
        ];
    }

    private function emptyStats(): array
    {
        return [
            'total_products' => 0,
            'high_stocks' => 0,
            'low_stocks' => 0,
        ];
    }

    private function normalize($product): array
    {
        $qty = $product->qty !== null ? (float) $product->qty : null;
        $restock = $product->restock_level !== null ? (float) $product->restock_level : null;

        return [
            'id' => $product->id,
            'item_no' => $product->item_no,
            'product' => $product->product,
            'brand' => $product->brand,
            'unit' => $product->unit,
            'qty' => $qty,
            'restock_level' => $restock,
            'stock_status' => $this->stockStatus($qty, $restock),
        ];
    }

    private function stockStatus(?float $qty, ?float $restock): array
    {
        if ($qty === null || $restock === null) {
            return ['label' => 'Unknown', 'tone' => 'unknown'];
        }
        if ($qty <= $restock) {
            return ['label' => 'Low Stock', 'tone' => 'low'];
        }
        $near = $restock + max(1, ceil($restock * 0.25));
        if ($qty <= $near) {
            return ['label' => 'Near Low Stock', 'tone' => 'near'];
        }
        return ['label' => 'High Stock', 'tone' => 'high'];
    }
}
