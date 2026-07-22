<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! $this->canSyncAssignments()) {
            return;
        }

        DB::table('sales_agents')
            ->select(['id', 'name'])
            ->orderBy('id')
            ->get()
            ->each(function ($agent): void {
                $name = trim((string) $agent->name);

                if ($name === '') {
                    return;
                }

                DB::table('customers')
                    ->whereNull('sales_agent_id')
                    ->whereRaw('LOWER(TRIM(salesman_name)) = ?', [strtolower($name)])
                    ->update([
                        'sales_agent_id' => $agent->id,
                        'updated_at' => now(),
                    ]);
            });

        DB::table('customers')
            ->join('sales_agents', 'customers.sales_agent_id', '=', 'sales_agents.id')
            ->whereNotNull('customers.sales_agent_id')
            ->where(function ($query): void {
                $query->whereNull('customers.salesman_name')
                    ->orWhere('customers.salesman_name', '');
            })
            ->update([
                'customers.salesman_name' => DB::raw('sales_agents.name'),
                'customers.updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        //
    }

    private function canSyncAssignments(): bool
    {
        return Schema::hasTable('customers')
            && Schema::hasTable('sales_agents')
            && Schema::hasColumn('customers', 'sales_agent_id')
            && Schema::hasColumn('customers', 'salesman_name')
            && Schema::hasColumn('sales_agents', 'name');
    }
};
