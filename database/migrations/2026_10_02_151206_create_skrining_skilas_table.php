<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('skrining_skilas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pemeriksaan_id')->constrained('pemeriksaan')->onDelete('cascade');
            $table->text('indikator_perilaku');
            $table->text('kemampuan_motorik_kognitif');
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('skrining_skilas');
    }
};