<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diklat', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('batch')->nullable();
            $table->string('lokasi')->default('Pemalang');
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->string('status')->default('Menunggu Jadwal Diklat'); // Menunggu Jadwal Diklat, Terjadwal, Sedang Mengikuti Diklat, Lulus Diklat, Tidak Lulus Diklat
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diklat');
    }
};
