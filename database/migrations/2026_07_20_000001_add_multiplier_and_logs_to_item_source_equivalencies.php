<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('item_source_equivalencies')) {
            Schema::table('item_source_equivalencies', function (Blueprint $table) {
                if (! Schema::hasColumn('item_source_equivalencies', 'multiplier')) {
                    $table->decimal('multiplier', 18, 6)->nullable()->after('item_source_id');
                }
            });

            if (
                ! $this->indexExists('item_source_equivalencies', 'item_source_equivalencies_item_source_id_unique')
                && ! $this->hasDuplicateItemSources()
            ) {
                Schema::table('item_source_equivalencies', function (Blueprint $table) {
                    $table->unique('item_source_id', 'item_source_equivalencies_item_source_id_unique');
                });
            }
        }

        if (! Schema::hasTable('item_source_equivalency_logs')) {
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
    }

    public function down(): void
    {
        Schema::dropIfExists('item_source_equivalency_logs');

        if (Schema::hasTable('item_source_equivalencies')) {
            Schema::table('item_source_equivalencies', function (Blueprint $table) {
                if ($this->indexExists('item_source_equivalencies', 'item_source_equivalencies_item_source_id_unique')) {
                    $table->dropUnique('item_source_equivalencies_item_source_id_unique');
                }

                if (Schema::hasColumn('item_source_equivalencies', 'multiplier')) {
                    $table->dropColumn('multiplier');
                }
            });
        }
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return false;
        }

        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('INDEX_NAME', $index)
            ->exists();
    }

    private function hasDuplicateItemSources(): bool
    {
        return DB::table('item_source_equivalencies')
            ->select('item_source_id')
            ->groupBy('item_source_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }
};
