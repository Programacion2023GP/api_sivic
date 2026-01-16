<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ApiResponse;
use App\Models\VwReincidencias;
use Illuminate\Support\Facades\Log;

class ReportResidencesController extends Controller
{

    public function index()
    {
        try {
            $traffic = \DB::select(
                "SELECT 
    COUNT(ac.id) as recidencias,
    GROUP_CONCAT(c.id ORDER BY c.id SEPARATOR ', ') as FoliosReincidencias,
    MAX(ac.id) as UltimoFolio,
    ac.name as Nombre
FROM alcohol_cases as ac 
LEFT JOIN alcohol_cases as c ON ac.id = c.residence_folio 
GROUP BY ac.id, ac.name
HAVING FoliosReincidencias IS NOT NULL 
   AND FoliosReincidencias != ''"
            );

            return ApiResponse::success($traffic, 'Registros de seguridad publica recuperados con éxito');
        } catch (\Exception $e) {
            Log::error("Error en Securitypublic::index: " . $e->getMessage());
            return ApiResponse::error($e->getMessage(), 500);
        }
    }
}
