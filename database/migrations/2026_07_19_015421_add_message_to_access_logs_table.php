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
        Schema::table('access_logs', function (Blueprint $table) {
            $table->string('message')->nullable()->after('status');
            $table->string('device_identifier')->nullable()->after('message');
            $table->string('scanned_code')->nullable()->after('device_identifier');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_logs', function (Blueprint $table) {
            $table->dropColumn(['message', 'device_identifier', 'scanned_code']);
        });
    }
};
