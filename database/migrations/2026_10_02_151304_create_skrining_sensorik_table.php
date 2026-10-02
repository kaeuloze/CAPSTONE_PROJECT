<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('skrining_sensorik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('pemeriksaan')->onDelete('cascade');
            $table->string('penglihatan_mata_kanan');
            $table->string('penglihatan_mata_kiri');
            $table->string('pendengaran_telinga_kanan');
            $table->string('pendengaran_telinga_kiri');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('skrining_sensorik');
    }
};