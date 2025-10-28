<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('amount', 10, 2);
            $table->longText('khqr_payload')->nullable();
            $table->string('khqr_md5')->nullable();
            $table->string('khqr_status')->default('pending');
            $table->timestamp('khqr_checked_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['khqr_payload', 'khqr_md5', 'khqr_status', 'khqr_checked_at']);
        });
    }
};
