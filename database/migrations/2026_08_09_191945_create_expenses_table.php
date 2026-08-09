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
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('concept');
            $table->text('description')->nullable();
            $table->string('category')->default('other')->comment('Enum: expense_category');
            $table->decimal('amount', 10, 2);
            $table->date('expense_date');
            $table->string('provider')->nullable()->comment('Proveedor o beneficiario del egreso');
            $table->string('payment_method')->nullable()->comment('Enum: payment_method');
            $table->string('status')->default('paid')->comment('Enum: expense_status');
            $table->string('receipt')->nullable()->comment('Ruta del comprobante adjunto');
            $table->foreignId('registered_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
