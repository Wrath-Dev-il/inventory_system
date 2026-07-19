<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('item_no')->unique();
            $table->string('product');
            $table->string('brand')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('qty', 14, 2)->default(0);
            $table->decimal('restock_level', 14, 2)->default(0);
            $table->string('item_source')->nullable();
            $table->enum('cost_currency', ['PHP', 'CNY'])->default('PHP');
            $table->decimal('cost_value', 14, 2)->default(0);
            $table->decimal('cost_in_yuan', 14, 2)->nullable();
            $table->decimal('cost_in_peso', 14, 2)->nullable();
            $table->decimal('selling_price', 14, 2)->default(0);
            $table->decimal('price_online', 14, 2)->nullable();
            $table->decimal('sea_freight', 14, 2)->default(0);
            $table->decimal('air_freight', 14, 2)->default(0);
            $table->timestamps();

            $table->index(['created_at', 'item_no']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
