<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\causeOfDetention;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CauseOfDetentionController extends Controller
{
    public function createorUpdate(Request $request)
    {
        try {
            // Si el IDDetalleTipo existe (>0), buscamos el registro para actualizar
            $motivo = $request->id > 0
                ? causeOfDetention::find($request->id)
                : new causeOfDetention();

            if (!$motivo) {
                return ApiResponse::error('Doctores no encontrados', 404);
            }

            // Rellenamos solo los campos permitidos

            $motivo->name = $request->name;
            $motivo->active = $request->active;

            $motivo->save();

            $message = $request->id > 0
                ? 'motivo de detención  actualizado'
                : 'motivo de detención  creado';

            return ApiResponse::success($motivo, $message);
        } catch (Exception $e) {
            return ApiResponse::error('ocurrio un error', 500);
        }
    }

    public function index()
    {
        try {
            $motivo = causeOfDetention::where("active", 1)->get();
            return ApiResponse::success($motivo, 'motivo de detención recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los doctores', 500);
        }
    }
    public function destroy(Request $request)
    {
        try {
            $motivo = causeOfDetention::find($request->id);

            if (!$motivo) {
                return ApiResponse::error("motivo de detención no encontradas", 404);
            }

            $motivo->update(['active' => false]);
            return ApiResponse::success(null, 'motivo de detención eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error("error " . $e->getMessage(), []);
            return ApiResponse::error('Error al eliminar la motivo de detención: ', 500);
        }
    }
}
