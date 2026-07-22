<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\SalesAgent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SalesAgentController extends Controller
{
    private array $sortableColumns = [
        'name',
        'phone',
        'commission_percentage',
        'date_started',
        'customers_count',
    ];

    private array $searchableColumns = [
        'name',
        'phone',
        'commission_percentage',
        'date_started',
    ];

    public function index(Request $request)
    {
        $agents = $this->paginatedAgents($request);

        return view('admin.Product-List.salesman-list', [
            'title' => 'Sales Agent List',
            'subtitle' => 'Manage sales agents and their customer assignments.',
            'agents' => $agents,
            'stats' => $this->metrics(),
            'searches' => $request->query('search', []),
            'sortColumn' => $request->query('sort', ''),
            'sortDirection' => $request->query('direction', ''),
            'nextAgentNo' => $this->nextAgentNo(),
            'agentStoreUrl' => route('admin.sales-agents.store'),
            'agentDetailsUrlTemplate' => route('admin.sales-agents.show', ['salesAgent' => '__AGENT_ID__']),
            'agentCustomersUrlTemplate' => route('admin.sales-agents.customers', ['salesAgent' => '__AGENT_ID__']),
            'agentUpdateUrlTemplate' => route('admin.sales-agents.update', ['salesAgent' => '__AGENT_ID__']),
            'agentDestroyUrlTemplate' => route('admin.sales-agents.destroy', ['salesAgent' => '__AGENT_ID__']),
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->storeRules());

        $agent = DB::transaction(function () use ($validated) {
            $agent = new SalesAgent($this->agentAttributes($validated));
            $agent->agent_no = $this->nextAgentNo(true);
            $agent->save();
            return $agent;
        });

        return response()->json([
            'message' => 'Sales agent added successfully.',
            'agent' => $this->normalizeAgent($agent),
            'stats' => $this->metrics(),
        ], 201);
    }

    public function show(SalesAgent $salesAgent): JsonResponse
    {
        $salesAgent->loadCount('customers');

        return response()->json([
            'agent' => $this->normalizeAgent($salesAgent),
        ]);
    }

    public function customers(Request $request, SalesAgent $salesAgent): JsonResponse
    {
        $perPage = 25;
        $customers = $salesAgent->customers()
            ->with(['priceReference', 'salesAgent'])
            ->orderBy('customer_name')
            ->paginate($perPage);
        $hasSalesOrders = Schema::hasTable('sales_orders');

        $customers->getCollection()->transform(function (Customer $customer) use ($hasSalesOrders) {
            $orders = $hasSalesOrders
                ? DB::table('sales_orders')
                    ->where('customer_id', $customer->id)
                    ->whereIn('status', ['Pending', 'Confirmed'])
                    ->where('payment_status', '!=', 'Paid')
                : null;

            return [
                'id' => $customer->id,
                'customer_name' => $customer->customer_name,
                'customer_no' => $customer->customer_no,
                'price_reference' => strtolower($customer->priceReference?->code ?? 'green'),
                'price_reference_label' => $customer->priceReference?->name ?? 'Green',
                'outstanding_invoices' => $orders ? (clone $orders)->count() : 0,
                'outstanding_total' => $orders ? (float) (clone $orders)->sum('total_with_vat') : 0.0,
            ];
        });

        return response()->json([
            'customers' => $customers->items(),
            'pagination' => [
                'current_page' => $customers->currentPage(),
                'last_page' => $customers->lastPage(),
                'total' => $customers->total(),
                'per_page' => $customers->perPage(),
                'from' => $customers->firstItem(),
                'to' => $customers->lastItem(),
            ],
        ]);
    }

    public function update(Request $request, SalesAgent $salesAgent): JsonResponse
    {
        $validated = $request->validate($this->updateRules($salesAgent->id));

        DB::transaction(function () use ($salesAgent, $validated) {
            $salesAgent->update($this->agentAttributes($validated, true));
        });

        return response()->json([
            'message' => 'Sales agent updated successfully.',
            'agent' => $this->normalizeAgent($salesAgent->fresh()),
            'stats' => $this->metrics(),
        ]);
    }

    public function destroy(SalesAgent $salesAgent): JsonResponse
    {
        $assignedCount = $salesAgent->customers()->count();

        if ($assignedCount > 0) {
            return response()->json([
                'blocked' => true,
                'message' => 'This sales agent has ' . $assignedCount . ' customer(s) assigned and cannot be deleted.',
                'agent' => $this->normalizeAgent($salesAgent),
            ], 409);
        }

        $deletedAgent = $this->normalizeAgent($salesAgent);

        DB::transaction(function () use ($salesAgent) {
            $salesAgent->delete();
        });

        return response()->json([
            'deleted' => true,
            'message' => 'Sales agent deleted successfully.',
            'agent' => $deletedAgent,
            'stats' => $this->metrics(),
        ]);
    }

    private function paginatedAgents(Request $request): LengthAwarePaginator
    {
        $query = SalesAgent::query()
            ->withCount('customers');

        $this->applySearch($query, $request);
        $this->applySort($query, $request);

        return $query->paginate(50)->withQueryString();
    }

    private function applySearch(Builder $query, Request $request): void
    {
        foreach ((array) $request->query('search', []) as $column => $term) {
            if (! in_array($column, $this->searchableColumns, true)) continue;

            $term = trim((string) $term);
            if ($term === '') continue;

            $query->where($column, 'like', '%' . $term . '%');
        }
    }

    private function applySort(Builder $query, Request $request): void
    {
        $column = $request->query('sort', '');
        $direction = $request->query('direction', 'asc');

        if (! in_array($column, $this->sortableColumns, true)) {
            $query->orderByDesc('created_at')->orderByDesc('id');
            return;
        }

        if (! in_array($direction, ['asc', 'desc'])) {
            $direction = 'asc';
        }

        if ($column === 'customers_count') {
            $query->orderBy('customers_count', $direction);
        } else {
            $query->orderBy($column, $direction);
        }
    }

    private function storeRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'date_started' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    private function updateRules(int $agentId): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'commission_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            'date_started' => ['required', 'date', 'before_or_equal:today'],
        ];
    }

    private function agentAttributes(array $payload, bool $isUpdate = false): array
    {
        $attributes = [
            'name' => trim((string) $payload['name']),
            'phone' => $this->nullableString($payload['phone'] ?? null),
            'commission_percentage' => (float) ($payload['commission_percentage'] ?? 0),
            'date_started' => $payload['date_started'] ?? null,
            'is_active' => true,
        ];

        if (! $isUpdate) {
            $attributes['email'] = null;
        }

        return $attributes;
    }

    private function normalizeAgent(SalesAgent $agent): array
    {
        return [
            'id' => $agent->id,
            'agent_no' => $agent->agent_no,
            'name' => $agent->name,
            'phone' => $agent->phone,
            'commission_percentage' => (float) ($agent->commission_percentage ?? 0),
            'date_started' => $agent->date_started?->toDateString(),
            'is_active' => $agent->is_active,
            'customers_count' => (int) ($agent->customers_count ?? $agent->customers()->count()),
            'created_at' => $agent->created_at?->toDateTimeString(),
        ];
    }

    private function metrics(): array
    {
        $total = SalesAgent::query()->where('is_active', true)->count();
        $assigned = SalesAgent::query()
            ->where('is_active', true)
            ->whereHas('customers')
            ->count();

        return [
            'total_agents' => $total,
            'assigned_agents' => $assigned,
            'unassigned_agents' => $total - $assigned,
        ];
    }

    private function nextAgentNo(bool $lock = false): string
    {
        $query = SalesAgent::query();

        if ($lock) {
            $query->lockForUpdate();
        }

        $last = $query
            ->withTrashed()
            ->where('agent_no', 'like', 'AGENT-%')
            ->orderByDesc('id')
            ->value('agent_no');

        $seq = 0;
        if ($last && preg_match('/AGENT-(\d+)/', $last, $matches)) {
            $seq = (int) $matches[1];
        }

        return 'AGENT-' . str_pad((string) ($seq + 1), 6, '0', STR_PAD_LEFT);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }
}
