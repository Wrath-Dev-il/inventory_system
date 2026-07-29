<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->binary('image_data');
            $table->binary('thumbnail_data');
            $table->string('mime_type', 50);
            $table->string('original_name', 255);
            $table->integer('file_size')->unsigned();
            $table->string('checksum', 64);
            $table->timestamps();

            $table->index('product_id');
        });

        DB::statement('ALTER TABLE `product_images` MODIFY `image_data` LONGBLOB');
        DB::statement('ALTER TABLE `product_images` MODIFY `thumbnail_data` MEDIUMBLOB');
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};