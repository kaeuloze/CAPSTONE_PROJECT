<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pemeriksaan_bumil', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('pemeriksaan')->onDelete('cascade');
            
            // Masa Kehamilan[cite: 18]
            $table->integer('usia_kehamilan')->nullable();
            $table->string('pelayanan_ttd_mms')->nullable(); // Tablet Tambah Darah / MMS[cite: 18]
            $table->string('mt_bumil_kek')->nullable(); // Makanan Tambahan Bumil KEK[cite: 18]
            
            // Masa Pasca-Persalinan[cite: 18]
            $table->string('pemberian_vitamin_a')->nullable();
            $table->string('status_menyusui')->nullable();
            $table->string('kb_pasca_persalinan')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('pemeriksaan_bumil');
    }
};