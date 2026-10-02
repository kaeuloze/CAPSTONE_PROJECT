<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('pengukuran_dasar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('pemeriksaan')->onDelete('cascade');
            $table->decimal('berat_badan', 5, 2);
            $table->decimal('tinggi_badan', 5, 2);
            $table->decimal('lingkar_perut', 5, 2)->nullable();
            $table->decimal('lila', 5, 2)->nullable(); // Lingkar Lengan Atas
            $table->string('tekanan_darah')->nullable();
            $table->decimal('kadar_gula_darah', 5, 2)->nullable();
            $table->decimal('kadar_hb', 4, 1)->nullable();
            $table->string('status_imt')->nullable();
            $table->string('status_tekanan_darah')->nullable();
            $table->string('status_lingkar_perut')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('pengukuran_dasar');
    }
};