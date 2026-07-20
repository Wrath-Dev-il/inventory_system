<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('customers') && ! Schema::hasColumn('customers', 'salesman_name')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->string('salesman_name', 150)->nullable()->after('sales_agent_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'salesman_name')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropColumn('salesman_name');
            });
        }
    }
};
