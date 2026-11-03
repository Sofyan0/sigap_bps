<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nonaktifkan sementara pengecekan foreign key
        Schema::disableForeignKeyConstraints();

        // Hapus tabel jika sudah pernah dibuat oleh migration terdahulu
        Schema::dropIfExists('commodities');

        // Buat tabel commodities baru dengan 24+ kolom lengkap
        Schema::create('commodities', function (Blueprint $table) {
            $table->id();
            $table->year('tahun');
            $table->string('id_dok')->nullable();
            $table->string('kuesioner')->nullable();
            $table->string('prov', 10)->default('35');
            $table->string('kab', 10)->default('12');
            $table->string('kec', 10)->nullable();
            $table->string('nama_kec')->nullable();
            $table->string('kode_kom', 20)->index();
            $table->string('nama_kom');
            $table->string('status_kom')->nullable();
            $table->string('kode_kualitas', 20)->nullable();
            $table->string('nama_kualitas')->nullable();
            $table->string('satuan', 20)->default('kg');
            $table->string('status_kualitas')->nullable();

            // Kolom Harga 12 Bulan (Prev & Current)
            $months = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember'];
            foreach ($months as $m) {
                $table->decimal($m . '_prev', 12, 2)->default(0);
                $table->decimal($m, 12, 2)->default(0);
            }

            $table->timestamps();
        });

        // Aktifkan kembali pengecekan foreign key
        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::disableForeignKeyConstraints();
        Schema::dropIfExists('commodities');
        Schema::enableForeignKeyConstraints();
    }
};