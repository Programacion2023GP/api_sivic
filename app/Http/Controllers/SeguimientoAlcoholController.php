<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\SeguimientoAlcohol;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SeguimientoAlcoholController extends Controller
{
   public function seguimiento(Request $request){
    try {
        $seg = SeguimientoAlcohol::where("case_id",$request->case_id)->get();
            return ApiResponse::success($seg, 'seguimiento del caso');

            //code...
        } catch (\Exception $e) {

            Log::error("Error en Seguimiento::index: " . $e->getMessage());
            return ApiResponse::error('Error al recuperar los registros', 500);
    }
   }
}
