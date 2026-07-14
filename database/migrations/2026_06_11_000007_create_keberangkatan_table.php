<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keberangkatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('maskapai')->nullable();
            $table->string('nomor_penerbangan')->nullable();
            $table->dateTime('tanggal_berangkat')->nullable();
            $table->string('negara_tujuan')->nullable();
            $table->string('status')->default('Tiket Diproses'); // Tiket Diproses, Jadwal Terbit, Berangkat, On Board
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keberangkatan');
    }
};
