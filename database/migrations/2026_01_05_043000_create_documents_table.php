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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Quién solicita o el protagonista
            $table->string('type'); // 'solicitud_chofer' o 'solicitud_unidad'
            $table->json('content'); // Aquí guardaremos placa, marca, dni, etc. (SNAPSHOT)
            $table->date('generated_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
