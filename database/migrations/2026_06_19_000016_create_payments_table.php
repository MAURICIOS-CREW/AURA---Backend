<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('charge_id')->constrained('financial_charges')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->string('payment_method')->comment('Enum: payment_method');
            $table->string('receipt')->nullable();
            $table->foreignId('validator_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('pending')->comment('Enum: payment_status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
