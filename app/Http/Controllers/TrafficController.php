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
            $traffic->citizen_name = $request->citizen_name;
            $traffic->age = $request->age;
            $traffic->rank = $request->rank;
            $traffic->plate_number = $request->plate_number;
            $traffic->vehicle_brand = $request->vehicle_brand;
            $traffic->time = $request->time;
            $traffic->location = $request->location;
            $traffic->person_oficial = $request->person_oficial;
            // $traffic->active = $request->active ?? 1;
            $data = $request->all();

            if ($request->hasFile('image_traffic') && $request->file('image_traffic')->isValid()) {
                $firma = $request->file('image_traffic');
                $dirPath = "presidencia/SIVIC/traffic/evidence";

                $imagePath = $this->ImgUpload(
                    $firma,
                    $request->citizen_name,
                    $dirPath,
                    $request->citizen_name
                );

                $traffic->image_traffic = "https://api.gpcenter.gomezpalacio.gob.mx/" .
                    $dirPath . "/" . $request->citizen_name . "/" . $imagePath;
            } else {
                // Si no hay archivo nuevo, eliminar la ruta temporal para no guardarla
                if (isset($data['image_traffic']) && str_contains($data['image_traffic'], 'Temp\\php')) {
                    unset($data['image_traffic']);
                }
            }
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