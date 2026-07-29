<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->string('attention_to', 255)->nullable()->after('prepared_by_name_snapshot');
        });

        Schema::table('sales_quotation_items', function (Blueprint $table) {
            $table->decimal('discount_percent', 5, 2)->default(0)->after('offer_description');
            $table->decimal('unit_price_without_tax_snapshot', 14, 2)->default(0)->after('discount_percent');
        });
    }

    public function down(): void
    {
        Schema::table('sales_quotations', function (Blueprint $table) {
            $table->dropColumn('attention_to');
        });

        Schema::table('sales_quotation_items', function (Blueprint $table) {
            $table->dropColumn(['discount_percent', 'unit_price_without_tax_snapshot']);
        });
    }
};
