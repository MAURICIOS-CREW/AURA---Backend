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
        Schema::create('contracted_services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained('services')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('residence_id')->constrained('residences')->cascadeOnDelete();
            $table->foreignId('charge_id')->nullable()->constrained('financial_charges')->nullOnDelete();
            
            $table->date('preferred_date');
            $table->time('visit_time_from');
            $table->time('visit_time_to');
            $table->dateTime('exact_scheduled_at')->nullable();
            
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['created', 'scheduled', 'in_progress', 'completed', 'refunded', 'cancelled'])->default('created');
            
            $table->text('notes')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('stripe_payment_id')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracted_services');
    }
};
