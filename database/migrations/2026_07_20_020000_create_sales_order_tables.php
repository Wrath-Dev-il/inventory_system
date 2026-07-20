<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_sequences', function (Blueprint $table) {
            $table->id();
            $table->char('month_year', 6)->unique();
            $table->integer('last_sequence')->default(0);
            $table->timestamps();
        });

        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_no', 20)->unique();
            $table->foreignId('customer_id')->constrained('customers');
            $table->string('customer_no_snapshot', 50)->nullable();
            $table->string('customer_name_snapshot', 255)->nullable();
            $table->string('tin_snapshot', 80)->nullable();
            $table->text('address_snapshot')->nullable();
            $table->string('price_reference_snapshot', 20)->nullable();
            $table->string('sales_agent_snapshot', 255)->nullable();
            $table->string('salesman_snapshot', 150)->nullable();
            $table->string('terms_snapshot', 120)->nullable();
            $table->string('sales_channel', 100);
            $table->date('order_date');
            $table->foreignId('prepared_by_user_id')->constrained('logins', 'login_ID');
            $table->string('prepared_by_name_snapshot', 255)->nullable();
            $table->string('payment_status', 20)->default('Unpaid');
            $table->string('status', 20)->default('Pending');
            $table->decimal('total_ordered_qty', 12, 2)->default(0);
            $table->decimal('total_without_vat', 14, 2)->default(0);
            $table->decimal('vat_exclusive_total', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('total_with_vat', 14, 2)->default(0);
            $table->dateTime('confirmed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products');
            $table->string('item_no_snapshot', 50)->nullable();
            $table->string('product_name_snapshot', 255)->nullable();
            $table->string('brand_snapshot', 255)->nullable();
            $table->string('unit_snapshot', 50)->nullable();
            $table->decimal('ordered_qty', 12, 2);
            $table->decimal('selling_price_snapshot', 14, 2);
            $table->decimal('discount_percent_snapshot', 5, 2)->default(0);
            $table->decimal('unit_price_without_vat', 14, 2)->default(0);
            $table->decimal('line_total_without_vat', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('line_total_with_vat', 14, 2)->default(0);
            $table->timestamps();
        });

        Schema::create('sales_order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained('sales_orders')->cascadeOnDelete();
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);
            $table->foreignId('changed_by')->constrained('logins', 'login_ID');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_status_logs');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
        Schema::dropIfExists('sales_order_sequences');
    }
};
