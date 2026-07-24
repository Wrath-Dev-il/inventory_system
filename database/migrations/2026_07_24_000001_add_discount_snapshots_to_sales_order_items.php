<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->decimal('discount_amount_snapshot', 14, 2)->default(0)->after('discount_percent_snapshot');
            $table->decimal('discounted_unit_price_snapshot', 14, 2)->default(0)->after('discount_amount_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('sales_order_items', function (Blueprint $table) {
            $table->dropColumn('discount_amount_snapshot');
            $table->dropColumn('discounted_unit_price_snapshot');
        });
    }
};