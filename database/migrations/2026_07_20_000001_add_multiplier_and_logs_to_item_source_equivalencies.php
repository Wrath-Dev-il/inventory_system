<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_source_equivalencies', function (Blueprint $table) {
            $table->decimal('multiplier', 18, 6)->nullable()->after('item_source_id');
            $table->unique('item_source_id', 'item_source_equivalencies_item_source_id_unique');
        });

        Schema::create('item_source_equivalency_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_source_id')->constrained('item_sources');
            $table->decimal('multiplier', 18, 6);
            $table->decimal('yuan_amount', 18, 4);
            $table->decimal('peso_amount', 18, 4);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('logged_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_source_equivalency_logs');

        Schema::table('item_source_equivalencies', function (Blueprint $table) {
            $table->dropUnique('item_source_equivalencies_item_source_id_unique');
            $table->dropColumn('multiplier');
        });
    }
};
