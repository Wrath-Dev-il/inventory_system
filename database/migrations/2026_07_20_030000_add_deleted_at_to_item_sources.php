<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('item_sources') && ! Schema::hasColumn('item_sources', 'deleted_at')) {
            Schema::table('item_sources', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('item_sources') && Schema::hasColumn('item_sources', 'deleted_at')) {
            Schema::table('item_sources', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
