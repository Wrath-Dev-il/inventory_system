<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ItemSource;
use App\Models\ItemSourceEquivalency;
use App\Services\CurrencyExchangeRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ProductConfigurationController extends Controller
{
    private CurrencyExchangeRateService $rateService;

    public function __construct(CurrencyExchangeRateService $rateService)
    {
        $this->rateService = $rateService;
    }

    public function index()
    {
        $sources = ItemSource::with('currentEquivalency')->orderBy('name')->get();
        $rateResult = $this->rateService->getCnyPhpRate();

        return view('admin.Product-List.product-configuration', [
            'sources' => $sources,
            'rate' => $rateResult['success'] ? $rateResult : null,
            'rateError' => !$rateResult['success'] ? ($rateResult['message'] ?? null) : null,
            'rateConfigured' => $this->rateService->isConfigured(),
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    public function storeSource(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:200', 'unique:item_sources,name'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $source = ItemSource::create([
            'name' => trim($request->input('name')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item Source Added.',
            'data' => [
                'item_source' => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'created_at' => $source->created_at->toDateTimeString(),
                ],
            ],
        ], 201);
    }

    public function listSources(): JsonResponse
    {
        $sources = ItemSource::with('currentEquivalency')->orderBy('name')->get(['id', 'name', 'created_at']);

        $data = $sources->map(function ($source) {
            return [
                'id' => $source->id,
                'name' => $source->name,
                'created_at' => $source->created_at->toDateTimeString(),
                'current_equivalency' => $source->currentEquivalency ? [
                    'multiplier' => (float) $source->currentEquivalency->multiplier,
                ] : null,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function updateSource(Request $request, $id): JsonResponse
    {
        $source = ItemSource::find($id);

        if (!$source) {
            return response()->json([
                'success' => false,
                'message' => 'Item source not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:200', 'unique:item_sources,name,' . $id],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $source->update([
            'name' => trim($request->input('name')),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Item Source Updated.',
            'data' => [
                'item_source' => [
                    'id' => $source->id,
                    'name' => $source->name,
                    'created_at' => $source->created_at->toDateTimeString(),
                ],
            ],
        ]);
    }

    public function destroySource($id): JsonResponse
    {
        $source = ItemSource::find($id);

        if (!$source) {
            return response()->json([
                'success' => false,
                'message' => 'Item source not found.',
            ], 404);
        }

        if (! Schema::hasColumn('item_sources', 'deleted_at')) {
            return response()->json([
                'blocked' => true,
                'success' => false,
                'message' => 'Run the latest migration first so item sources can be hidden without removing conversion logs or product links.',
            ]);
        }

        $source->delete();

        return response()->json([
            'success' => true,
            'message' => 'Item Source removed from the active list. Conversion logs and linked products were kept.',
        ]);
    }

    public function refreshRate(): JsonResponse
    {
        $result = $this->rateService->refreshRate();

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Unable to refresh currency rate.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'message' => 'Currency Rate Updated.',
            'data' => [
                'rate' => $result['rate'],
                'provider' => $result['provider'],
                'retrieved_at' => $result['retrieved_at'],
                'cached' => $result['cached'] ?? false,
            ],
        ]);
    }

    public function storeEquivalency(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'item_source_id' => ['required', 'integer', 'exists:item_sources,id'],
            'multiplier' => ['required', 'numeric', 'min:0.0001'],
            'yuan_amount' => ['nullable', 'numeric', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please correct the highlighted fields.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $itemSourceId = (int) $request->input('item_source_id');
        $multiplier = (float) $request->input('multiplier');
        $yuanAmount = (float) ($request->input('yuan_amount', 1));
        $pesoAmount = $yuanAmount * $multiplier;

        try {
            $result = DB::transaction(function () use ($itemSourceId, $multiplier, $yuanAmount, $pesoAmount, $request) {
                $oldEquiv = ItemSourceEquivalency::where('item_source_id', $itemSourceId)->first();
                $oldMultiplier = $oldEquiv ? (float) $oldEquiv->multiplier : null;

                $equivalency = ItemSourceEquivalency::updateOrCreate(
                    ['item_source_id' => $itemSourceId],
                    [
                        'multiplier' => $multiplier,
                        'yuan_amount' => $yuanAmount,
                        'peso_amount' => $pesoAmount,
                        'created_by' => $request->user()?->login_ID,
                        'converted_at' => now(),
                    ]
                );

                if (Schema::hasTable('item_source_equivalency_logs')) {
                    DB::table('item_source_equivalency_logs')->insert([
                        'item_source_id' => $itemSourceId,
                        'multiplier' => $multiplier,
                        'yuan_amount' => $yuanAmount,
                        'peso_amount' => $pesoAmount,
                        'created_by' => $request->user()?->login_ID,
                        'logged_at' => now(),
                    ]);
                }

                $linkedProducts = DB::table('products')->where('item_source_id', $itemSourceId)->get();
                $updatedCount = 0;
                $skippedCount = 0;

                foreach ($linkedProducts as $product) {
                    $costYuan = $product->cost_in_yuan;

                    if ($costYuan === null) {
                        $skippedCount++;
                        continue;
                    }

                    $newPeso = round((float) $costYuan * $multiplier, 2);

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'cost_in_peso' => $newPeso,
                            'cost_currency' => 'PHP',
                            'cost_value' => $newPeso,
                            'updated_at' => now(),
                        ]);

                    $updatedCount++;
                }

                $equivalency->load('itemSource');

                return [
                    'equivalency' => $equivalency,
                    'old_multiplier' => $oldMultiplier,
                    'updated_count' => $updatedCount,
                    'skipped_count' => $skippedCount,
                    'linked_count' => $linkedProducts->count(),
                ];
            });

            $parts = [];

            if ($result['old_multiplier'] !== null) {
                $parts[] = 'Multiplier updated from ' . number_format($result['old_multiplier'], 6) . ' to ' . number_format($multiplier, 6) . '.';
            } else {
                $parts[] = 'Multiplier set to ' . number_format($multiplier, 6) . '.';
            }

            if ($result['linked_count'] > 0) {
                $parts[] = $result['updated_count'] . ' of ' . $result['linked_count'] . ' linked Product' . ($result['linked_count'] !== 1 ? 's' : '') . ' Peso cost' . ($result['updated_count'] !== 1 ? 's were' : ' was') . ' recalculated.';

                if ($result['skipped_count'] > 0) {
                    $parts[] = $result['skipped_count'] . ' skipped (missing Yuan cost).';
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Equivalency Saved. ' . implode(' ', $parts),
                'data' => [
                    'equivalency' => [
                        'id' => $result['equivalency']->id,
                        'item_source' => $result['equivalency']->itemSource?->name,
                        'multiplier' => (float) $result['equivalency']->multiplier,
                        'yuan_amount' => (float) $result['equivalency']->yuan_amount,
                        'peso_amount' => (float) $result['equivalency']->peso_amount,
                    ],
                    'product_updates' => [
                        'linked_count' => $result['linked_count'],
                        'updated_count' => $result['updated_count'],
                        'skipped_count' => $result['skipped_count'],
                    ],
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to save equivalency. Please try again.',
            ], 500);
        }
    }

    public function listLogs(Request $request): JsonResponse
    {
        if (! Schema::hasTable('item_source_equivalency_logs')) {
            return response()->json([
                'success' => true,
                'data' => [],
                'pagination' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 25,
                    'total' => 0,
                    'from' => null,
                    'to' => null,
                ],
            ]);
        }

        $perPage = 25;
        $query = DB::table('item_source_equivalency_logs')
            ->join('item_sources', 'item_source_equivalency_logs.item_source_id', '=', 'item_sources.id')
            ->select('item_source_equivalency_logs.*', 'item_sources.name as item_source_name');

        $sortField = $request->query('sort', 'date');
        $sortDir = $request->query('dir', 'desc');

        $sortAllowlist = [
            'item_source' => 'item_sources.name',
            'multiplier' => 'item_source_equivalency_logs.multiplier',
            'yuan' => 'item_source_equivalency_logs.yuan_amount',
            'peso' => 'item_source_equivalency_logs.peso_amount',
            'date' => 'item_source_equivalency_logs.logged_at',
        ];

        if (!array_key_exists($sortField, $sortAllowlist)) {
            $sortField = 'date';
        }

        $sortDir = $sortDir === 'asc' ? 'asc' : 'desc';

        if ($request->filled('search_item_source')) {
            $search = $request->input('search_item_source');
            $query->where('item_sources.name', 'like', '%' . $search . '%');
        }

        if ($request->filled('search_multiplier')) {
            $search = $request->input('search_multiplier');
            $query->where('item_source_equivalency_logs.multiplier', 'like', '%' . $search . '%');
        }

        if ($request->filled('search_yuan')) {
            $search = $request->input('search_yuan');
            $query->where('item_source_equivalency_logs.yuan_amount', 'like', '%' . $search . '%');
        }

        if ($request->filled('search_peso')) {
            $search = $request->input('search_peso');
            $query->where('item_source_equivalency_logs.peso_amount', 'like', '%' . $search . '%');
        }

        if ($request->filled('search_date')) {
            $search = $request->input('search_date');
            $query->whereDate('item_source_equivalency_logs.logged_at', $search);
        }

        $query->orderBy($sortAllowlist[$sortField], $sortDir);

        $paginator = $query->paginate($perPage);

        $logs = collect($paginator->items())->map(function ($item) {
            return [
                'id' => $item->id,
                'item_source' => $item->item_source_name ?? 'Unknown',
                'multiplier' => (float) $item->multiplier,
                'yuan_amount' => (float) $item->yuan_amount,
                'peso_amount' => (float) $item->peso_amount,
                'logged_at' => $item->logged_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $logs,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
        ]);
    }
}
