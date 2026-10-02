<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('warga', function (Blueprint $table) {
            $table->string('nik', 16)->primary(); // NIK sebagai primary key
            $table->string('nama');
            $table->string('jenis_kelamin');
            $table->date('tanggal_lahir');
            $table->text('alamat');
            $table->string('status_kondisi'); // e.g. Hamil, Nifas, Menyusui, Normal, dll.
            $table->string('kategori_usia'); // Penentu jenis pemeriksaan
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('warga');
    }
};