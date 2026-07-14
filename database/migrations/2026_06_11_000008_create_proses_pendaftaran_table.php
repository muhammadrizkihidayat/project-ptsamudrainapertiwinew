<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proses_pendaftaran', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->integer('tahap')->default(1); // 1: Registrasi & Dokumen, 2: MCU, 3: Diklat, 4: Dokumen Pelaut, 5: Waiting List, 6: Keberangkatan (7: Berangkat, 8: On Board, dll.)
            $table->string('status')->default('Dalam Proses'); // Dalam Proses, Selesai, Revisi
            $table->text('catatan')->nullable();
            $table->timestamp('tanggal_update')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proses_pendaftaran');
    }
};
