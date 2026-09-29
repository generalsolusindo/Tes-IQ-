<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('absensi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->date('tanggal');

            $table->dateTime('jam_masuk')->nullable();
            $table->decimal('latitude_masuk', 10, 7)->nullable();
            $table->decimal('longitude_masuk', 10, 7)->nullable();
            $table->unsignedInteger('akurasi_masuk_meter')->nullable();
            $table->unsignedInteger('jarak_masuk_meter')->nullable();
            $table->string('foto_masuk_path')->nullable();
            $table->string('status_masuk', 20)->nullable();

            $table->dateTime('jam_pulang')->nullable();
            $table->decimal('latitude_pulang', 10, 7)->nullable();
            $table->decimal('longitude_pulang', 10, 7)->nullable();
            $table->unsignedInteger('akurasi_pulang_meter')->nullable();
            $table->unsignedInteger('jarak_pulang_meter')->nullable();
            $table->string('foto_pulang_path')->nullable();
            $table->string('status_pulang', 20)->nullable();

            $table->timestamps();

            $table->unique(['user_id', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};
