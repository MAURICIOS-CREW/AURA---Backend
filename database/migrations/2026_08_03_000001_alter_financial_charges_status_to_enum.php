<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE financial_charges MODIFY COLUMN status ENUM('pending', 'paid', 'cancelled', 'refunded') NOT NULL DEFAULT 'pending'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE financial_charges MODIFY COLUMN status VARCHAR(255) NOT NULL DEFAULT 'pending'");
        }
    }
};
