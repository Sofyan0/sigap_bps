<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('harga_pasar')) {
            Schema::create('harga_pasar', function (Blueprint $table) {
                $table->id();
                $table->string('excel_id')->nullable();
                $table->string('nama_pasar');
                $table->string('nama_barang');
                $table->decimal('harga', 14, 2);
                $table->date('tanggal_dibuat');
                $table->timestamps();

                $table->index(['nama_pasar', 'tanggal_dibuat']);
                $table->index(['nama_barang', 'tanggal_dibuat']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('harga_pasar');
    }
};
