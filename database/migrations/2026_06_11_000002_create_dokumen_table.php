<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('jenis_dokumen'); // ktp, kartu_keluarga, skck, akta_kelahiran, ijazah
            $table->string('file_path');
            $table->string('status')->default('Menunggu Verifikasi'); // Menunggu Verifikasi, Disetujui, Ditolak
            $table->text('catatan_operator')->nullable();
            $table->timestamp('tanggal_upload')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen');
    }
};
