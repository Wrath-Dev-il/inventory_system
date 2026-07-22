<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_agents', function (Blueprint $table) {
            $table->decimal('commission_percentage', 5, 2)->default(0.00)->after('phone');
            $table->date('date_started')->nullable()->after('commission_percentage');
            $table->softDeletes()->after('updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('sales_agents', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['commission_percentage', 'date_started']);
        });
    }
};
