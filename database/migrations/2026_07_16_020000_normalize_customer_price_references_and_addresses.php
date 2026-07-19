<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('price_references')) {
            Schema::create('price_references', function (Blueprint $table) {
                $table->id();
                $table->string('code', 40)->unique();
                $table->string('name');
                $table->decimal('default_discount_percent', 5, 2)->default(0);
                $table->timestamps();
            });
        }

        $this->seedPriceReferences();

        if (Schema::hasTable('customers') && ! Schema::hasColumn('customers', 'price_reference_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->foreignId('price_reference_id')
                    ->nullable()
                    ->after('tin')
                    ->constrained('price_references')
                    ->restrictOnDelete();

                $table->index('customer_name', 'customers_customer_name_index');
                $table->index('tin', 'customers_tin_index');
                $table->index('date_started', 'customers_date_started_index');
            });
        }

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'price_reference_id')) {
            if (Schema::hasColumn('customers', 'price_reference')) {
                DB::statement("
                    UPDATE customers c
                    INNER JOIN price_references p ON p.code = UPPER(c.price_reference)
                    SET c.price_reference_id = p.id
                    WHERE c.price_reference_id IS NULL
                ");
            }

            DB::table('customers')
                ->whereNull('price_reference_id')
                ->update(['price_reference_id' => DB::table('price_references')->where('code', 'GREEN')->value('id')]);
        }

        if (! Schema::hasTable('customer_addresses')) {
            Schema::create('customer_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->unique()->constrained('customers')->cascadeOnDelete();
                $table->text('formatted_address')->nullable();
                $table->decimal('latitude', 10, 7)->nullable();
                $table->decimal('longitude', 10, 7)->nullable();
                $table->timestamps();
            });
        }

        $this->copyLegacyCustomerAddresses();
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_addresses');

        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'price_reference_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropConstrainedForeignId('price_reference_id');
                $table->dropIndex('customers_customer_name_index');
                $table->dropIndex('customers_tin_index');
                $table->dropIndex('customers_date_started_index');
            });
        }

        Schema::dropIfExists('price_references');
    }

    private function seedPriceReferences(): void
    {
        $now = now();

        foreach ([
            ['code' => 'GREEN', 'name' => 'Green', 'default_discount_percent' => 0],
            ['code' => 'YELLOW', 'name' => 'Yellow', 'default_discount_percent' => 20],
        ] as $reference) {
            DB::table('price_references')->updateOrInsert(
                ['code' => $reference['code']],
                [
                    'name' => $reference['name'],
                    'default_discount_percent' => $reference['default_discount_percent'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }

    private function copyLegacyCustomerAddresses(): void
    {
        if (
            ! Schema::hasTable('customers')
            || ! Schema::hasTable('customer_addresses')
            || ! Schema::hasColumn('customers', 'address')
        ) {
            return;
        }

        DB::statement("
            INSERT INTO customer_addresses (customer_id, formatted_address, latitude, longitude, created_at, updated_at)
            SELECT c.id, c.address, c.latitude, c.longitude, NOW(), NOW()
            FROM customers c
            WHERE (c.address IS NOT NULL OR c.latitude IS NOT NULL OR c.longitude IS NOT NULL)
              AND NOT EXISTS (
                  SELECT 1
                  FROM customer_addresses ca
                  WHERE ca.customer_id = c.id
              )
        ");
    }
};
