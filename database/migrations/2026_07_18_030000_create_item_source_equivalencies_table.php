<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_source_equivalencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_source_id')->constrained('item_sources');
            $table->foreignId('exchange_rate_id')->nullable()->constrained('exchange_rates');
            $table->decimal('yuan_amount', 18, 4);
            $table->decimal('peso_amount', 18, 4);
            $table->decimal('rate_used', 20, 8);
            $table->timestamp('converted_at')->useCurrent();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->index('item_source_id');
            $table->index('yuan_amount');
            $table->index('peso_amount');
            $table->index('converted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_source_equivalencies');
    }
};
