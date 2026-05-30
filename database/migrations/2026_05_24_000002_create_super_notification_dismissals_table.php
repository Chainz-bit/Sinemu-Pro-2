<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('super_notification_dismissals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('super_admin_id')->constrained('super_admins')->cascadeOnDelete();
            $table->string('item_key');
            $table->timestamp('dismissed_at')->nullable();
            $table->timestamps();

            $table->unique(['super_admin_id', 'item_key']);
            $table->index('super_admin_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('super_notification_dismissals');
    }
};
