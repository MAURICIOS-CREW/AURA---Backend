<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('db_host')->nullable();
            $table->string('db_name')->nullable();
            $table->string('db_user')->nullable();
            $table->string('db_password')->nullable();
            $table->string('status')->default('active')->comment('Enum: tenant_status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
