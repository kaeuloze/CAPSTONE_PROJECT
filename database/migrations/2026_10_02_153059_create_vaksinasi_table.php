<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('vaksinasi', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 16);
            $table->foreignId('vaksin_id')->constrained('vaksin')->onDelete('cascade');
            $table->foreignId('kegiatan_id')->constrained('kegiatan')->onDelete('cascade');
            $table->string('jenis_vaksinasi');
            $table->string('status_vaksinasi');
            $table->date('tanggal_pelaksanaan');
            $table->timestamps();

            $table->foreign('nik')->references('nik')->on('warga')->onDelete('cascade');
        });
    }

    public function down(): void {
        Schema::dropIfExists('vaksinasi');
    }
};