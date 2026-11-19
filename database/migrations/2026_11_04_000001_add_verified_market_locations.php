<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->boolean('location_verified')->default(false);
            $table->string('location_address')->nullable();
            $table->string('location_source_url')->nullable();
        });

        DB::table('markets')->update([
            'location_verified' => false,
            'location_address' => null,
            'location_source_url' => null,
        ]);

        DB::table('markets')->where('code', 'SIT-BESUKI')->update([
            'name' => 'Pasar Rakyat Besuki',
            'latitude' => -7.7334240,
            'longitude' => 113.6922420,
            'location_verified' => true,
            'location_address' => 'Besuki, Situbondo, Jawa Timur',
            'location_source_url' => 'https://www.openstreetmap.org/node/13986031602',
        ]);

        DB::table('markets')->where('code', 'SIT-PANARUKAN')->update([
            'latitude' => -7.6963087,
            'longitude' => 113.9411047,
            'location_verified' => true,
            'location_address' => 'Kilensari, Situbondo, Jawa Timur',
            'location_source_url' => 'https://www.openstreetmap.org/way/328282561',
        ]);

        DB::table('markets')->where('code', 'SIT-ASEMBAGUS')->update([
            'latitude' => -7.7490374,
            'longitude' => 114.2181617,
            'location_verified' => true,
            'location_address' => 'Jalan Raya Banyuwangi, Asembagus, Situbondo, Jawa Timur',
            'location_source_url' => 'https://www.openstreetmap.org/way/328697375',
        ]);

        DB::table('markets')->where('code', 'SIT-PANJI')->update([
            'name' => 'Pasar Mimbaan',
            'latitude' => -7.7058543,
            'longitude' => 114.0110516,
            'location_verified' => true,
            'location_address' => 'Jalan Sapudi, Situbondo, Jawa Timur',
            'location_source_url' => 'https://www.openstreetmap.org/way/240971078',
        ]);
    }

    public function down(): void
    {
        Schema::table('markets', function (Blueprint $table) {
            $table->dropColumn(['location_verified', 'location_address', 'location_source_url']);
        });
    }
};
