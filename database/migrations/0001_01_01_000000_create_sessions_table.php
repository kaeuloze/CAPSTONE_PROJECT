<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabel bawaan Laravel untuk menyimpan sesi login (SESSION_DRIVER=database).
 * Kolom user_id berisi id_kader dari tabel kader (tanpa foreign key, sesuai bawaan Laravel).
 * Tabel users & password_reset_tokens bawaan Laravel sengaja tidak dibuat:
 * autentikasi memakai tabel KADER (ERD Gambar 2.2).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->unsignedInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessions');
    }
};
