<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('skrining_puma', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('pemeriksaan')->onDelete('cascade');
            $table->integer('skor_kebiasaan_merokok');
            $table->text('gejala_pernapasan');
            $table->integer('total_skor_risiko_paru');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('skrining_puma');
    }
};