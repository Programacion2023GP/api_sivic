<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Publicsecurities;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class PublicSecuritiesController extends Controller
{
    public function createOrUpdate(Request $request)
    {
        try {
            $security = null;
            if ($security = $request->id > 0) {
                $security = Publicsecurities::find($request->id);
            }
            else{
                $security =new Publicsecurities();
                $security->created_by = Auth::id();

            }

            if (!$security && $request->id > 0) {
                return ApiResponse::error('Registro de seguridad pública no encontrado', 404);
            }

            // Asignar campos
            $security->detainee_name = $request->detainee_name;
            $security->officer_name = $request->officer_name;
            $security->patrol_unit_number = $request->patrol_unit_number;
            $security->detention_reason = $request->detention_reason;
            $security->date = $request->date;
            $security->time = $request->time;
            $security->age = $request->age;
            $security->location = $request->location;
            $security->active = $request->active ?? 1;

            $security->save();

            $message = $request->id > 0
                ? 'Registro actualizado correctamente'
                : 'Registro creado correctamente';

            return ApiResponse::success($security, $message);
        } catch (\Exception $e) {
            Log::error("Error en PublicSecurityController::createOrUpdate: " . $e->getMessage());
            return ApiResponse::error('Error al guardar el registro', 500);
        }
    }


    public function index()
    {
        try {
            $traffic = Publicsecurities::where("active", 1)->get();
            return ApiResponse::success($traffic, 'Registros de seguridad publica recuperados con éxito');
        } catch (\Exception $e) {
            Log::error("Error en Securitypublic::index: " . $e->getMessage());
            return ApiResponse::error('Error al recuperar los registros de seguridad publica', 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $traffic = Publicsecurities::find($request->id);

            if (!$traffic) {
                return ApiResponse::error("Registro de de segurida publica no encontrado", 404);
            }

            $traffic->update(['active' => false]);
            return ApiResponse::success(null, 'Registro de de segurida publica eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error("Error en Securitypublic::destroy: " . $e->getMessage());
            return ApiResponse::error('Error al eliminar el registro de de segurida publica', 500);
        }
    }
}
