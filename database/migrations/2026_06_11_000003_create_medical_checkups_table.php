<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_checkups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('file_hasil_mcu')->nullable();
            $table->string('status_mcu')->default('Menunggu Upload Hasil MCU'); // Menunggu Upload Hasil MCU, Menunggu Verifikasi MCU, Lulus MCU, Pending MCU, Tidak Lulus MCU
            $table->text('catatan_operator')->nullable();
            $table->timestamp('tanggal_upload')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_checkups');
    }
};
