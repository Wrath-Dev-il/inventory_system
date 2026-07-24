<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->unique()->constrained('sales_orders')->cascadeOnDelete();
            $table->date('billing_date')->nullable();
            $table->date('due_date')->nullable();
            $table->string('transaction_type', 20)->nullable();
            $table->string('po_no', 100)->nullable();
            $table->string('sales_invoice_no', 100)->nullable();
            $table->string('quotation_no', 100)->nullable();
            $table->string('initial_payment_status', 20)->default('unpaid');
            $table->string('final_payment_status', 20)->default('unpaid');
            $table->text('actual_payment_remarks')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('logins', 'login_ID')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_listings');
    }
};
