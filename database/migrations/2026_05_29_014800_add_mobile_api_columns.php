<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'alamat')) {
            Schema::table('users', function (Blueprint $table) {
                $table->text('alamat')->nullable()->after('nomor_telepon');
            });
        }

        if (
            Schema::hasTable('laporan_barang_hilangs')
            && Schema::hasTable('kategoris')
            && ! Schema::hasColumn('laporan_barang_hilangs', 'kategori_id')
        ) {
            Schema::table('laporan_barang_hilangs', function (Blueprint $table) {
                $table->foreignId('kategori_id')
                    ->nullable()
                    ->after('region_id')
                    ->constrained('kategoris')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('klaims') && ! Schema::hasColumn('klaims', 'kontak')) {
            Schema::table('klaims', function (Blueprint $table) {
                $table->string('kontak', 50)->nullable()->after('catatan');
            });
        }

        if (
            Schema::hasTable('klaims')
            && Schema::hasColumn('klaims', 'laporan_hilang_id')
        ) {
            Schema::table('klaims', function (Blueprint $table) {
                $table->unsignedBigInteger('laporan_hilang_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable('klaims')
            && Schema::hasColumn('klaims', 'laporan_hilang_id')
            && DB::table('klaims')->whereNull('laporan_hilang_id')->doesntExist()
        ) {
            Schema::table('klaims', function (Blueprint $table) {
                $table->unsignedBigInteger('laporan_hilang_id')->nullable(false)->change();
            });
        }

        if (Schema::hasTable('klaims') && Schema::hasColumn('klaims', 'kontak')) {
            Schema::table('klaims', function (Blueprint $table) {
                $table->dropColumn('kontak');
            });
        }

        if (Schema::hasTable('laporan_barang_hilangs') && Schema::hasColumn('laporan_barang_hilangs', 'kategori_id')) {
            Schema::table('laporan_barang_hilangs', function (Blueprint $table) {
                $table->dropConstrainedForeignId('kategori_id');
            });
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'alamat')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('alamat');
            });
        }
    }
};
