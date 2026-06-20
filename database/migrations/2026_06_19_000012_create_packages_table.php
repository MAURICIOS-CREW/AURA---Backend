<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('residence_id')->constrained('residences')->cascadeOnDelete();
            $table->foreignId('receiver_guard_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('courier_company')->nullable();
            $table->string('status')->default('pending')->comment('Enum: package_status');
            $table->string('delivery_evidence')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
