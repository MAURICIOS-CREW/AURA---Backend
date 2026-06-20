<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('access_logs', function (Blueprint $table) {
            $table->id();
            $table->string('access_type')->comment('Enum: access_type');
            $table->string('method')->comment('Enum: access_method');
            $table->foreignId('residence_id')->nullable()->constrained('residences')->nullOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignId('access_code_id')->nullable()->constrained('access_codes')->nullOnDelete();
            $table->foreignId('guard_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->comment('Enum: access_status');
            $table->float('ai_confidence')->nullable();
            $table->timestamp('timestamp')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('access_logs');
    }
};
