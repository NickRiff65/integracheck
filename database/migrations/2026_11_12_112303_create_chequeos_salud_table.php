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
        Schema::create('chequeos_salud', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integracion_id')
                ->constrained('integraciones')
                ->cascadeOnDelete();
            $table->string('tipo');
            $table->boolean('resultado');
            $table->unsignedInteger('latencia_ms')->nullable();
            $table->text('detalle')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chequeos_salud');
    }
};
