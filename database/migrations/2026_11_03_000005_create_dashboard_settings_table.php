<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dashboard_settings', function (Blueprint $table) {
            $table->id();
            $table->string('admin_name')->default('Admin TPID Situbondo');
            $table->string('email')->nullable();
            $table->string('institution')->default('Bagian Perekonomian Setda');
            $table->string('employee_id', 50)->nullable();
            $table->boolean('email_ews_enabled')->default(true);
            $table->boolean('weekly_iph_enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_settings');
    }
};
