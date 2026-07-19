<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->unsignedBigInteger('item_source_id')->nullable()->after('item_source');
            $table->foreign('item_source_id')->references('id')->on('item_sources')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['item_source_id']);
            $table->dropColumn('item_source_id');
        });
    }
};
