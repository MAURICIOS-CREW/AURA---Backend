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
        Schema::table('contracted_services', function (Blueprint $table) {
            $table->boolean('is_recurrent')->default(false)->after('status');
            $table->json('suggested_schedule')->nullable()->after('is_recurrent');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contracted_services', function (Blueprint $table) {
            $table->dropColumn(['is_recurrent', 'suggested_schedule']);
        });
    }
};
