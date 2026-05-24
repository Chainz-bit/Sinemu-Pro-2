<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->index(['admin_id', 'read_at'], 'admin_notifications_admin_id_read_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('admin_notifications', function (Blueprint $table) {
            $table->dropIndex('admin_notifications_admin_id_read_at_index');
        });
    }
};
