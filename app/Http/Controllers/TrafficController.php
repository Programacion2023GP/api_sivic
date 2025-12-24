<?php

namespace App\Http\Controllers;

use App\Models\Traffic;
use App\Models\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class TrafficController extends Controller
{
    public function createorUpdate(Request $request)
    {
        try {
            $traffic = null;
            if ($traffic = $request->id > 0) {
                $traffic = Traffic::find($request->id);
            } else {
                $traffic = new Traffic();
                $traffic->created_by = Auth::id();
            }


            if (!$traffic && $request->id > 0) {
                return ApiResponse::error('Registro de tránsito no encontrado', 404);
            }

            // Asignar campos manualmente
         
            $traffic->vehicle_brand = $request->vehicle_brand;
       
            // $traffic->active = $request->active ?? 1;

        
            $traffic->save();

            $message = $request->id > 0 
                ? 'Registro de tránsito actualizado' 
                : 'Registro de tránsito creado';

            return ApiResponse::success($traffic, $message);

        } catch (\Exception $e) {
            Log::error("Error en TrafficController::createorUpdate: " . $e->getMessage());
            return ApiResponse::error('Error al guardar el registro de tránsito', 500);
        }
    }

    public function index()
    {
        try {
            $traffic = Traffic::where("active", 1)->orderBy('id', 'desc')->get();
            return ApiResponse::success($traffic, 'Registros de tránsito recuperados con éxito');
        } catch (\Exception $e) {
            Log::error("Error en TrafficController::index: " . $e->getMessage());
            return ApiResponse::error('Error al recuperar los registros de tránsito', 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $traffic = Traffic::find($request->id);

            if (!$traffic) {
                return ApiResponse::error("Registro de tránsito no encontrado", 404);
            }

            $traffic->update(['active' => false]);
            return ApiResponse::success(null, 'Registro de tránsito eliminado correctamente.');

        } catch (\Exception $e) {
            Log::error("Error en TrafficController::destroy: " . $e->getMessage());
            return ApiResponse::error('Error al eliminar el registro de tránsito', 500);
        }
    }
}