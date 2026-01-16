<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // PRIMERO: Agregar los campos action y observation si no existen
        Schema::table('alcohol_case_process_history', function (Blueprint $table) {
            // Verificar si ya existe la columna 'action'
            if (!Schema::hasColumn('alcohol_case_process_history', 'action')) {
                $table->enum('action', [
                    'creacion',
                    'actualizacion',
                    'finalizado',
                    'cancelacion',
                    'cambio proceso',
                    'rechazado',
                ])->default('creacion');
            }

            // Verificar si ya existe la columna 'observation'
            if (!Schema::hasColumn('alcohol_case_process_history', 'observation')) {
                $table->text('observation')->nullable();
            }
        });

        // SEGUNDO: Crear los triggers
    }

  

    public function down(): void
    {
        // Primero eliminar los triggers
    

        // Luego eliminar las columnas
        Schema::table('alcohol_case_process_history', function (Blueprint $table) {
            $table->dropColumn(['action', 'observation']);
        });
    }
};
