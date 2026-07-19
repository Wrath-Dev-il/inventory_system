<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\PriceReference;
use App\Models\SalesAgent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    private array $customerColumns = [
        'customer_no',
        'customer_name',
        'tin',
        'price_reference',
        'discount_percent',
        'sales_agent',
        'address',
        'date_started',
        'terms',
    ];

    private array $editableColumns = [
        'customer_name',
        'tin',
        'price_reference',
        'discount_percent',
        'sales_agent_id',
        'address',
        'date_started',
        'terms',
    ];

    public function index(Request $request)
    {
        $tableExists = Schema::hasTable('customers')
            && Schema::hasTable('sales_agents')
            && Schema::hasTable('price_references')
            && Schema::hasTable('customer_addresses');
        $customers = $tableExists ? $this->paginatedCustomers($request) : null;
        $stats = $tableExists ? $this->customerStats() : $this->emptyStats();
        $salesAgents = $tableExists ? $this->salesAgentOptions() : collect();

        return view('admin.Product-List.customer-list', [
            'title' => 'Customer List',
            'subtitle' => 'Manage customer records, pricing references and sales assignments.',
            'tableExists' => $tableExists,
            'customers' => $customers,
            'stats' => $stats,
            'salesAgents' => $salesAgents,
            'searches' => $request->query('search', []),
            'globalSearch' => $request->query('q', ''),
            'nextCustomerNo' => $tableExists ? $this->nextCustomerNo() : 'CTRLA-000001',
            'customerStoreUrl' => route('admin.customers.store'),
            'customerDetailsUrlTemplate' => route('admin.customers.show', ['customer' => '__CUSTOMER_ID__']),
            'customerUpdateUrl' => route('admin.customers.bulk-update'),
            'customerDestroyUrlTemplate' => route('admin.customers.destroy', ['customer' => '__CUSTOMER_ID__']),
            'companyName' => config('app.name', 'CONTROL A Trading and Services'),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $payload = $this->validatedCustomerPayload($request);

        $customer = DB::transaction(function () use ($payload) {
            $priceReference = $this->priceReferenceForCode($payload['price_reference']);
            $customer = new Customer($this->customerAttributes($payload, $priceReference));
            $customer->customer_no = $this->nextCustomerNo(true);
            $customer->save();
            $this->saveCustomerAddress($customer, $payload);

            return $customer->fresh(['salesAgent', 'priceReference', 'customerAddress']);
        });

        return response()->json([
            'message' => 'Customer added successfully.',
            'customers' => [$this->normalizeCustomer($customer)],
            'stats' => $this->customerStats(),
            'next_customer_no' => $this->nextCustomerNo(),
        ], 201);
    }

    public function show(Customer $customer): JsonResponse
    {
        return response()->json([
            'customer' => $this->normalizeCustomer($customer->load(['salesAgent', 'priceReference', 'customerAddress'])),
        ]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'edits' => ['required', 'array', 'min:1'],
            'edits.*.id' => ['required', 'integer', 'exists:customers,id'],
            'edits.*.field' => ['required', 'string', Rule::in($this->editableColumns)],
            'edits.*.value' => ['nullable'],
        ]);

        $updated = DB::transaction(function () use ($validated) {
            $customers = collect();

            foreach ($validated['edits'] as $edit) {
                $customer = Customer::query()->lockForUpdate()->findOrFail($edit['id']);
                $this->applyInlineEdit($customer, $edit['field'], $edit['value']);
                $this->enforceCustomerRules($customer);
                $customer->save();
                $customers->put($customer->id, $customer->fresh(['salesAgent', 'priceReference', 'customerAddress']));
            }

            return $customers->values();
        });

        return response()->json([
            'message' => 'Customer changes saved successfully.',
            'customers' => $updated->map(fn (Customer $customer) => $this->normalizeCustomer($customer))->values(),
            'stats' => $this->customerStats(),
        ]);
    }

    public function destroy(Request $request, Customer $customer): JsonResponse
    {
        abort_unless((int) $request->user()?->account_type === 1, 403);

        $deletedCustomer = $this->normalizeCustomer($customer->load(['salesAgent', 'priceReference', 'customerAddress']));

        if ($this->customerHasBlockingReferences($customer)) {
            return response()->json([
                'message' => 'This customer is already referenced by sales or transaction records and cannot be deleted.',
            ], 409);
        }

        try {
            DB::transaction(function () use ($customer) {
                $customer->delete();
            });
        } catch (QueryException) {
            return response()->json([
                'message' => 'This customer is already referenced by sales or transaction records and cannot be deleted.',
            ], 409);
        }

        return response()->json([
            'message' => 'Customer deleted successfully.',
            'customer' => $deletedCustomer,
            'stats' => $this->customerStats(),
            'next_customer_no' => $this->nextCustomerNo(),
        ]);
    }

    private function paginatedCustomers(Request $request): LengthAwarePaginator
    {
        $query = Customer::query()
            ->with(['salesAgent', 'priceReference', 'customerAddress'])
            ->orderByDesc('created_at')
            ->orderByDesc('id');

        $this->applyCustomerSearch($query, $request);

        $paginator = $query->paginate(50)->withQueryString();
        $paginator->getCollection()->transform(fn (Customer $customer) => $this->normalizeCustomer($customer));

        return $paginator;
    }

    private function applyCustomerSearch(Builder $query, Request $request): void
    {
        $globalSearch = trim((string) $request->query('q', ''));

        if ($globalSearch !== '') {
            $query->where(function (Builder $nested) use ($globalSearch) {
                foreach (['customer_no', 'customer_name', 'tin', 'discount_percent', 'date_started', 'terms'] as $column) {
                    $nested->orWhere($column, 'like', '%'.$globalSearch.'%');
                }

                $nested->orWhereHas('salesAgent', function (Builder $agent) use ($globalSearch) {
                    $agent->where('name', 'like', '%'.$globalSearch.'%')
                        ->orWhere('agent_no', 'like', '%'.$globalSearch.'%');
                });

                $nested->orWhereHas('priceReference', function (Builder $reference) use ($globalSearch) {
                    $reference->where('name', 'like', '%'.$globalSearch.'%')
                        ->orWhere('code', 'like', '%'.$globalSearch.'%');
                });

                $nested->orWhereHas('customerAddress', function (Builder $address) use ($globalSearch) {
                    $address->where('formatted_address', 'like', '%'.$globalSearch.'%');
                });
            });
        }

        foreach ((array) $request->query('search', []) as $column => $term) {
            if (! in_array($column, $this->customerColumns, true)) {
                continue;
            }

            $term = trim((string) $term);

            if ($term === '') {
                continue;
            }

            if ($column === 'sales_agent') {
                $query->whereHas('salesAgent', function (Builder $agent) use ($term) {
                    $agent->where('name', 'like', '%'.$term.'%')
                        ->orWhere('agent_no', 'like', '%'.$term.'%');
                });
                continue;
            }

            if ($column === 'price_reference') {
                $query->whereHas('priceReference', function (Builder $reference) use ($term) {
                    $reference->where('name', 'like', '%'.$term.'%')
                        ->orWhere('code', 'like', '%'.$term.'%');
                });
                continue;
            }

            if ($column === 'address') {
                $query->whereHas('customerAddress', function (Builder $address) use ($term) {
                    $address->where('formatted_address', 'like', '%'.$term.'%');
                });
                continue;
            }

            $query->where($column, 'like', '%'.$term.'%');
        }
    }

    private function validatedCustomerPayload(Request $request): array
    {
        return $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'tin' => ['nullable', 'string', 'max:80'],
            'price_reference' => ['required', Rule::in(['green', 'yellow'])],
            'discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sales_agent_id' => ['nullable', 'integer', 'exists:sales_agents,id'],
            'address' => ['required', 'string', 'max:2000'],
            'date_started' => ['nullable', 'date'],
            'terms' => ['nullable', 'string', 'max:120'],
        ]);
    }

    private function customerAttributes(array $payload, PriceReference $priceReference): array
    {
        $referenceCode = strtolower((string) $priceReference->code);
        $discount = $referenceCode === 'yellow'
            ? 20.0
            : ($this->numericValue($payload['discount_percent'] ?? null) ?? 0.0);

        return [
            'customer_name' => trim((string) $payload['customer_name']),
            'tin' => $this->nullableString($payload['tin'] ?? null),
            'price_reference_id' => $priceReference->id,
            'discount_percent' => $discount,
            'sales_agent_id' => $payload['sales_agent_id'] ?? null,
            'date_started' => $payload['date_started'] ?? null,
            'terms' => $this->nullableString($payload['terms'] ?? null),
        ];
    }

    private function applyInlineEdit(Customer $customer, string $field, mixed $value): void
    {
        if (in_array($field, ['customer_name', 'tin', 'address', 'date_started', 'terms'], true)) {
            if ($field === 'address') {
                $this->saveCustomerAddress($customer, ['address' => $value]);
                return;
            }

            $customer->{$field} = $field === 'customer_name'
                ? trim((string) $value)
                : $this->nullableString($value);
            return;
        }

        if ($field === 'price_reference') {
            $customer->price_reference_id = $this->priceReferenceForCode($value)->id;
            return;
        }

        if ($field === 'discount_percent') {
            $customer->discount_percent = $this->numericValue($value) ?? 0;
            return;
        }

        if ($field === 'sales_agent_id') {
            $customer->sales_agent_id = $value !== null && $value !== '' ? (int) $value : null;
            return;
        }
    }

    private function enforceCustomerRules(Customer $customer): void
    {
        $code = $customer->priceReference?->code
            ?? PriceReference::query()->whereKey($customer->price_reference_id)->value('code');

        if (strtolower((string) $code) === 'yellow') {
            $customer->discount_percent = 20;
        }
    }

    private function customerStats(): array
    {
        return [
            'total_customers' => Customer::query()->count(),
            'yellow_customers' => Customer::query()->whereHas('priceReference', fn (Builder $query) => $query->where('code', 'YELLOW'))->count(),
            'green_customers' => Customer::query()->whereHas('priceReference', fn (Builder $query) => $query->where('code', 'GREEN'))->count(),
            'total_sales_agents' => SalesAgent::query()->where('is_active', true)->count(),
        ];
    }

    private function emptyStats(): array
    {
        return [
            'total_customers' => 0,
            'yellow_customers' => 0,
            'green_customers' => 0,
            'total_sales_agents' => 0,
        ];
    }

    private function salesAgentOptions()
    {
        return SalesAgent::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (SalesAgent $agent) => [
                'id' => $agent->id,
                'agent_no' => $agent->agent_no,
                'name' => $agent->name,
            ]);
    }

    private function nextCustomerNo(bool $lock = false): string
    {
        $query = Customer::query();

        if ($lock) {
            $query->lockForUpdate();
        }

        $last = $query
            ->where(function ($q) {
                $q->where('customer_no', 'like', 'CUST-%')
                  ->orWhere('customer_no', 'like', 'CTRLA-%');
            })
            ->orderByDesc('id')
            ->value('customer_no');

        $seq = 0;

        if ($last && preg_match('/(CUST|CTRLA)-(\d+)/', $last, $matches)) {
            $seq = (int) $matches[2];
        }

        return 'CTRLA-'.str_pad((string) ($seq + 1), 6, '0', STR_PAD_LEFT);
    }

    private function normalizeCustomer(Customer $customer): array
    {
        $agent = $customer->salesAgent;
        $priceReference = $customer->priceReference;
        $address = $customer->customerAddress;
        $referenceCode = strtolower((string) ($priceReference?->code ?? 'green'));

        return [
            'id' => $customer->id,
            'customer_no' => $customer->customer_no,
            'customer_name' => $customer->customer_name,
            'tin' => $customer->tin,
            'price_reference' => $referenceCode === 'yellow' ? 'yellow' : 'green',
            'price_reference_label' => $priceReference?->name ?? ucfirst($referenceCode),
            'discount_percent' => $this->numericValue($customer->discount_percent),
            'sales_agent_id' => $customer->sales_agent_id,
            'sales_agent' => $agent?->name,
            'sales_agent_no' => $agent?->agent_no,
            'address' => $address?->formatted_address,
            'date_started' => $customer->date_started?->toDateString(),
            'terms' => $customer->terms,
            'created_at' => $customer->created_at?->toDateTimeString(),
        ];
    }

    private function customerHasBlockingReferences(Customer $customer): bool
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
               AND REFERENCED_TABLE_NAME = 'customers'
               AND REFERENCED_COLUMN_NAME = 'id'",
            [$database]
        ));

        $customerIdColumns = collect(DB::select(
            "SELECT TABLE_NAME, COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = ?
               AND TABLE_NAME <> 'customers'
               AND COLUMN_NAME = 'customer_id'",
            [$database]
        ));

        return $references
            ->merge($customerIdColumns)
            ->unique(fn ($reference) => $reference->TABLE_NAME.'.'.$reference->COLUMN_NAME)
            ->reject(fn ($reference) => $reference->TABLE_NAME === 'customer_addresses')
            ->contains(function ($reference) use ($customer) {
                return Schema::hasTable($reference->TABLE_NAME)
                    && Schema::hasColumn($reference->TABLE_NAME, $reference->COLUMN_NAME)
                    && DB::table($reference->TABLE_NAME)->where($reference->COLUMN_NAME, $customer->id)->exists();
            });
    }

    private function priceReferenceForCode(mixed $code): PriceReference
    {
        $normalized = strtolower((string) $code) === 'yellow' ? 'YELLOW' : 'GREEN';

        return PriceReference::query()->firstOrCreate(
            ['code' => $normalized],
            [
                'name' => ucfirst(strtolower($normalized)),
                'default_discount_percent' => $normalized === 'YELLOW' ? 20 : 0,
            ]
        );
    }

    private function saveCustomerAddress(Customer $customer, array $payload): void
    {
        CustomerAddress::query()->updateOrCreate(
            ['customer_id' => $customer->id],
            [
                'formatted_address' => $this->nullableString($payload['address'] ?? null),
            ]
        );
    }

    private function numericValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (float) $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
