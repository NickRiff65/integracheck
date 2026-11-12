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
        Schema::create('conciliacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conciliacion_id')->constrained('conciliaciones')->cascadeOnDelete();
            $table->enum('tipo', ['carga', 'total_activo']);
            $table->string('origen');
            $table->unsignedInteger('cantidad')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conciliacion_detalles');
    }
};
