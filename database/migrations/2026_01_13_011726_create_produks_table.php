<?php

use Illuminate\Database\Migrations\Migration; // fungsinya untuk memanggil blueprint dasar untuk sebuah migrasi
use Illuminate\Database\Schema\Blueprint; // fungsinya untuk memberi akses ke objek $table
use Illuminate\Support\Facades\Schema; // fungsinya untuk memanggil facade (antarmuka sederhana) untuk berinteraski dengan database
// facade adalah sebuah "pintu pintas" atau antarmuka sederhana untuk mengakses fitur fitur kompleks yang ada di dalam sistem laravel

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('produks', function (Blueprint $table) {
            $table->id();
            $table->string(column: 'name');
            $table->string(column: 'slug');
            $table->string(column: 'thumbnail');
            $table->text(column: 'about');
            $table->unsignedBigInteger(column: 'price');
            $table->unsignedBigInteger(column: 'stock');
            $table->boolean(column: 'is_populer');
            $table->foreignId(column: 'category_id')->constrained()->cascadeOnDelete();
            $table->foreignId(column: 'brand_id')->constrained()->cascadeOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('produks');
    }
};
