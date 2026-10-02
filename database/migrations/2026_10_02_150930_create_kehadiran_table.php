<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('kehadiran', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16);
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->onDelete('cascade');
            $table->string('status_kehadiran');
            $table->timestamp('waktu_kehadiran')->nullable();
            $table->timestamps();

            $table->foreign('nik')->references('nik')->on('warga')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('kehadiran');
    }
};