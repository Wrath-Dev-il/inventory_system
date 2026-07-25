<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexExists = DB::select("
            SELECT 1 FROM information_schema.statistics
            WHERE table_schema = ?
              AND table_name = 'item_sources'
              AND index_name = 'item_sources_name_unique'
        ", [DB::getDatabaseName()]);

        if (!empty($indexExists)) {
            Schema::table('item_sources', function (Blueprint $table) {
                $table->dropUnique('item_sources_name_unique');
            });
        }
    }

    public function down(): void
    {
        Schema::table('item_sources', function (Blueprint $table) {
            $table->unique('name', 'item_sources_name_unique');
        });
    }
};