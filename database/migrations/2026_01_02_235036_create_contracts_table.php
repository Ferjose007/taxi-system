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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Empleado
            $table->foreignId('vehicle_id')->constrained('vehicles'); // Auto asignado
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('type'); // 'alquiler', 'puerta_libre', etc
            $table->string('pdf_path')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
