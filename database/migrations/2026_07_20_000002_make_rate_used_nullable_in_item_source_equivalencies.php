<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('item_source_equivalencies', function (Blueprint $table) {
            $table->decimal('rate_used', 20, 8)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('item_source_equivalencies', function (Blueprint $table) {
            $table->decimal('rate_used', 20, 8)->nullable(false)->change();
        });
    }
};
