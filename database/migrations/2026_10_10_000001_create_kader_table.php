<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Entitas KADER (ERD Gambar 2.2): akun pengguna sistem (kader posyandu).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kader', function (Blueprint $table) {
            $table->increments('id_kader');
            $table->string('username', 50)->unique();
            $table->string('password_hash', 255); // hash bcrypt, tidak pernah plaintext
            $table->string('nama_kader', 100);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kader');
    }
};
