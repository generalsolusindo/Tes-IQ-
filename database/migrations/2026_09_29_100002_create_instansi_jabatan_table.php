<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instansi_jabatan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instansi_id')->constrained('instansi')->cascadeOnDelete();
            $table->foreignId('jabatan_id')->constrained('jabatan')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['instansi_id', 'jabatan_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instansi_jabatan');
    }
};
