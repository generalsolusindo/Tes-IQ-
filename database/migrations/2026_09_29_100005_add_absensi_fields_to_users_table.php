<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Existing admin/merchant users predate the instansi/jabatan concept, so
     * instansi_id and jabatan_id are added nullable, backfilled to a default
     * instansi/jabatan below, then locked to NOT NULL — keeping the columns
     * mandatory for every role without breaking rows that already exist.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('instansi_id')->nullable()->after('password')->constrained('instansi')->restrictOnDelete();
            $table->foreignId('jabatan_id')->nullable()->after('instansi_id')->constrained('jabatan')->restrictOnDelete();
            $table->string('no_whatsapp', 20)->nullable()->after('email');
            $table->string('status', 20)->default('pending')->after('jabatan_id');
        });

        $instansiId = DB::table('instansi')->insertGetId([
            'nama' => 'Kantor Pusat',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $jabatanId = DB::table('jabatan')->insertGetId([
            'nama' => 'Staff Admin',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('instansi_jabatan')->insert([
            'instansi_id' => $instansiId,
            'jabatan_id' => $jabatanId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('users')->update([
            'instansi_id' => $instansiId,
            'jabatan_id' => $jabatanId,
            'status' => 'aktif',
        ]);

        DB::statement('ALTER TABLE users MODIFY instansi_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE users MODIFY jabatan_id BIGINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('instansi_id');
            $table->dropConstrainedForeignId('jabatan_id');
            $table->dropColumn(['no_whatsapp', 'status']);
        });
    }
};
