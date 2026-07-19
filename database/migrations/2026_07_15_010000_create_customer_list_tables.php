<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_agents', function (Blueprint $table) {
            $table->id();
            $table->string('agent_no')->unique();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('customer_no')->unique();
            $table->string('customer_name');
            $table->string('tin')->nullable();
            $table->enum('price_reference', ['green', 'yellow'])->default('green');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->foreignId('sales_agent_id')->nullable()->constrained('sales_agents')->nullOnDelete();
            $table->text('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->date('date_started')->nullable();
            $table->string('terms')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'customer_no']);
            $table->index('price_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('sales_agents');
    }
};
