<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('previous_qty', 14, 2);
            $table->decimal('new_qty', 14, 2);
            $table->decimal('difference', 14, 2);
            $table->string('adjustment_type')->default('manual');
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('adjusted_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_adjustments');
    }
};
