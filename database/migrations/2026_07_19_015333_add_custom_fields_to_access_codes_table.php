<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('access_codes', function (Blueprint $table) {
            $table->integer('max_uses')->nullable()->after('uses');
            $table->json('active_days')->nullable()->after('max_uses');
            $table->time('start_time')->nullable()->after('active_days');
            $table->time('end_time')->nullable()->after('start_time');
            $table->boolean('is_active')->default(true)->after('end_time');
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_codes', function (Blueprint $table) {
            $table->dropColumn(['max_uses', 'active_days', 'start_time', 'end_time', 'is_active']);
            $table->dropSoftDeletes();
        });
    }
};
