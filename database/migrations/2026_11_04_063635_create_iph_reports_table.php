<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('iph_reports', function (Blueprint $table) {
            $table->id();
            
            // Periode Pencatatan
            $table->date('period_date')->comment('Tanggal/Periode laporan IPH');
            $table->tinyInteger('week_of_month')->nullable()->comment('Minggu ke-1 s/d 5');

            // Wilayah Administrative
            $table->string('regency_code', 20)->index()->comment('Kode Kabupaten/Kota');
            $table->string('island_name', 100)->nullable()->comment('Pulau');
            $table->string('province_name', 100)->nullable()->comment('Provinsi');
            $table->string('regency_name', 100)->comment('Kabupaten/Kota');

            // Indikator IPH & Status
            $table->decimal('iph_change', 8, 2)->nullable()->comment('Perubahan IPH (%)');
            $table->string('status', 50)->nullable()->comment('Status IPH (misal: Naik, Turun, Stabil)');
            
            // Komoditas & Fluktuasi
            $table->text('main_commodities')->nullable()->comment('Komoditas Utama Penyumbang');
            $table->text('highest_price_fluctuation')->nullable()->comment('Fluktuasi Harga Tertinggi Minggu Berjalan');
            
            // Analisis Statistik Harga
            $table->decimal('cv_value', 8, 2)->nullable()->comment('Nilai CV (Nilai Fluktuasi)');
            $table->decimal('regional_price_disparity', 12, 2)->nullable()->comment('Disparitas Harga antar Daerah');

            // Upaya & Saran Pemda
            $table->text('pemda_action_monev')->nullable()->comment('Upaya Pemda (Monev APP)');
            $table->text('pemda_recommendation')->nullable()->comment('Saran Kepada Pemda');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('iph_reports');
    }
};