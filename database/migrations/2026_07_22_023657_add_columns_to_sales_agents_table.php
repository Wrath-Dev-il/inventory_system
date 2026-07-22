<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sales_agents')) {
            return;
        }

        Schema::table('sales_agents', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_agents', 'commission_percentage')) {
                $table->decimal('commission_percentage', 5, 2)->default(0.00)->after('phone');
            }

            if (! Schema::hasColumn('sales_agents', 'date_started')) {
                $table->date('date_started')->nullable()->after('commission_percentage');
            }

            if (! Schema::hasColumn('sales_agents', 'deleted_at')) {
                $table->softDeletes()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('sales_agents')) {
            return;
        }

        Schema::table('sales_agents', function (Blueprint $table) {
            if (Schema::hasColumn('sales_agents', 'deleted_at')) {
                $table->dropSoftDeletes();
            }

            foreach (['commission_percentage', 'date_started'] as $column) {
                if (Schema::hasColumn('sales_agents', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
