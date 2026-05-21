<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            if (! Schema::hasColumn('admins', 'pickup_address')) {
                $table->string('pickup_address')->nullable()->after('alamat_lengkap');
            }

            if (! Schema::hasColumn('admins', 'pickup_lat')) {
                $table->decimal('pickup_lat', 10, 7)->nullable()->after('pickup_address');
            }

            if (! Schema::hasColumn('admins', 'pickup_lng')) {
                $table->decimal('pickup_lng', 10, 7)->nullable()->after('pickup_lat');
            }
        });
    }

    public function down(): void
    {
        Schema::table('admins', function (Blueprint $table) {
            foreach (['pickup_lng', 'pickup_lat', 'pickup_address'] as $column) {
                if (Schema::hasColumn('admins', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
