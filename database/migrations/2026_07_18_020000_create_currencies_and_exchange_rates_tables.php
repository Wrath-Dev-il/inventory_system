<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3)->unique();
            $table->string('name', 100);
            $table->string('symbol', 10)->nullable();
            $table->timestamps();
        });

        DB::table('currencies')->insert([
            ['code' => 'CNY', 'name' => 'Chinese Yuan', 'symbol' => '¥'],
            ['code' => 'PHP', 'name' => 'Philippine Peso', 'symbol' => '₱'],
        ]);

        Schema::create('exchange_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_currency_id')->constrained('currencies');
            $table->foreignId('to_currency_id')->constrained('currencies');
            $table->decimal('rate', 20, 8);
            $table->string('provider', 100)->nullable();
            $table->string('provider_reference', 255)->nullable();
            $table->timestamp('effective_at')->nullable();
            $table->timestamps();

            $table->index(['from_currency_id', 'to_currency_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exchange_rates');
        Schema::dropIfExists('currencies');
    }
};
