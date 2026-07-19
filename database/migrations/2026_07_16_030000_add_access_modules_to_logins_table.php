<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('logins', function (Blueprint $table) {
            $table->text('access_modules')->nullable()->after('Password');
        });
    }

    public function down(): void
    {
        Schema::table('logins', function (Blueprint $table) {
            $table->dropColumn('access_modules');
        });
    }
};
