<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('nama_kapal')->nullable();
            $table->string('negara_tujuan')->nullable();
            $table->string('posisi')->nullable();
            $table->string('status_job')->default('Waiting List'); // Waiting List, Job Tersedia, Dipilih untuk Job, Persiapan Keberangkatan
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_orders');
    }
};
