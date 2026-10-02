<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('skrining_tbc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('pemeriksaan')->onDelete('cascade');
            $table->boolean('batuk_terus_menerus')->default(false);
            $table->boolean('demam_berkepanjangan')->default(false);
            $table->boolean('riwayat_kontak_pasien_tbc')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('skrining_tbc');
    }
};