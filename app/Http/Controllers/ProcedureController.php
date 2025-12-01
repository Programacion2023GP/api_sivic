<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Procedure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ProcedureController extends Controller
{
       public function createorUpdate(Request $request)
    {
        try {
            // Si el IDDetalleTipo existe (>0), buscamos el registro para actualizar
            $procedure = $request->id > 0
                ? Procedure::find($request->id)
                : new Procedure();

            if (!$procedure) {
                return ApiResponse::error('tramite no encontrado', 404);
            }

            // Rellenamos solo los campos permitidos

            $procedure->name = $request->name;
            $procedure->active = $request->active;

            $procedure->save();

            $message = $request->id > 0
                ? 'tramite  actualizado'
                : 'tramite  creado';

            return ApiResponse::success($procedure, $message);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function index()
    {
        try {
            $procedure = Procedure::where("active", 1)->get();
            return ApiResponse::success($procedure, 'tramites recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los tramites', 500);
        }
    }
    public function destroy(Request $request)
    {
        try {
            $procedure = Procedure::find($request->id);

            if (!$procedure) {
                return ApiResponse::error("tramites no encontrados", 404);
            }

            $procedure->update(['active' => false]);
            return ApiResponse::success(null, 'tramite eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error("error ". $e->getMessage(),[]);
            return ApiResponse::error('Error al eliminar el tramite: ', 500);
        }
    }

}
