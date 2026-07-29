<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_quotation_sequences', function (Blueprint $table) {
            $table->id();
            $table->year('year');
            $table->tinyInteger('month');
            $table->unsignedSmallInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['year', 'month']);
        });

        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('quotation_no', 30)->unique()->index();
            $table->date('quotation_date');
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('customer_no_snapshot')->nullable();
            $table->string('customer_name_snapshot')->nullable();
            $table->string('price_reference_snapshot')->nullable();
            $table->string('terms_snapshot')->nullable();
            $table->string('tin_snapshot')->nullable();
            $table->text('address_snapshot')->nullable();
            $table->string('sales', 150)->nullable();
            $table->text('remarks')->nullable();
            $table->text('payment_terms')->nullable();
            $table->text('cancellation_terms')->nullable();
            $table->text('delivery_terms')->nullable();
            $table->dateTime('lead_time_at')->nullable();
            $table->dateTime('valid_until')->nullable();
            $table->string('warranty')->nullable();
            $table->string('mode_of_payment')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('grand_total', 14, 2)->default(0);
            $table->string('status', 30)->default('confirmed');
            $table->unsignedBigInteger('prepared_by_user_id');
            $table->string('prepared_by_name_snapshot')->nullable();
            $table->timestamps();

            $table->foreign('prepared_by_user_id')->references('login_ID')->on('logins')->restrictOnDelete();
            $table->index(['created_at', 'quotation_no']);
        });

        Schema::create('sales_quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_quotation_id')->constrained('sales_quotations')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->string('item_no_snapshot')->nullable();
            $table->string('item_name_snapshot')->nullable();
            $table->string('brand_snapshot')->nullable();
            $table->string('unit_snapshot')->nullable();
            $table->decimal('available_quantity_snapshot', 14, 2)->default(0);
            $table->decimal('quantity', 14, 2)->default(1);
            $table->text('offer_description')->nullable();
            $table->decimal('unit_price', 14, 2)->default(0);
            $table->decimal('unit_price_with_tax', 14, 2)->default(0);
            $table->decimal('tax_amount', 14, 2)->default(0);
            $table->decimal('line_total', 14, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_quotation_items');
        Schema::dropIfExists('sales_quotations');
        Schema::dropIfExists('sales_quotation_sequences');
    }
};
