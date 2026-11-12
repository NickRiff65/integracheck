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
        Schema::create('conciliaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integracion_id')->constrained('integraciones')->cascadeOnDelete();
            $table->foreignId('envio_id')->nullable()->constrained('envios')->nullOnDelete();
            $table->string('archivo_origen')->nullable();
            $table->string('destino')->nullable();
            $table->unsignedInteger('registros_esperados')->default(0);
            $table->unsignedInteger('registros_registrados')->default(0);
            $table->unsignedInteger('registros_nuevos')->default(0);
            $table->unsignedInteger('registros_existentes')->default(0);
            $table->unsignedInteger('registros_faltantes')->default(0);
            $table->unsignedInteger('registros_duplicados')->default(0);
            $table->unsignedInteger('total_activos')->nullable();
            $table->timestamp('fecha_conciliacion');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conciliaciones');
    }
};
