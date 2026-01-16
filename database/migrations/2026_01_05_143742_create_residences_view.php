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
     

        DB::statement("
        create view vw_reincidencias_resumen as
        WITH RECURSIVE arbol_reincidencias AS (
  -- Casos base (sin reincidencia)
  SELECT 
    id,
    name,
    plate_number,
    alcohol_level,
    date,
    city,
    residence_folio,
    1 as nivel,
    CAST(id AS CHAR(255)) as arbol_path,
    CAST(CONCAT(name, ' (', plate_number, ')') AS CHAR(255)) as ruta_nombres
  FROM alcohol_cases 
  WHERE residence_folio IS NULL
  
  UNION ALL
  
  -- Casos recurrentes
  SELECT 
    ac.id,
    ac.name,
    ac.plate_number,
    ac.alcohol_level,
    ac.date,
    ac.city,
    ac.residence_folio,
    ar.nivel + 1,
    CONCAT(ar.arbol_path, ' → ', ac.id),
    CONCAT(ar.ruta_nombres, ' → ', ac.name, ' (', ac.alcohol_level, ' g/L)')
  FROM alcohol_cases ac
  INNER JOIN arbol_reincidencias ar ON ac.residence_folio = ar.id
)
SELECT 
  name as 'Nombre',
  plate_number as 'Placa',
  MAX(nivel) as 'Total Reincidencias',
  GROUP_CONCAT(
    CONCAT('N', nivel, ': ', alcohol_level, ' g/L (', date, ')') 
    ORDER BY nivel SEPARATOR ' | '
  ) as 'Historial por Niveles',
  ruta_nombres as 'Cadena Completa'
FROM arbol_reincidencias
GROUP BY name, plate_number, ruta_nombres
HAVING MAX(nivel) > 1
ORDER BY MAX(nivel) DESC, name
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS vw_reincidencias_resumen');
    }
};
