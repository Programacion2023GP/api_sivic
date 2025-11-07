<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Dependence;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;


class DependenceController extends Controller
{
    public function createorUpdate(Request $request)
    {
        try {
            // Si el IDDetalleTipo existe (>0), buscamos el registro para actualizar
            $dependence = $request->id > 0
                ? Dependence::find($request->id)
                : new Dependence();

            if (!$dependence) {
                return ApiResponse::error('Dependencia no encontrada', 404);
            }

            // Rellenamos solo los campos permitidos

            $dependence->name = $request->name;
            $dependence->active = $request->active;
            $dependence->color = $request->color;

            $dependence->save();

            $message = $request->id > 0
                ? 'Dependencia  actualizada'
                : 'Dependencia  creada';

            return ApiResponse::success($dependence, $message);
        } catch (Exception $e) {
            return ApiResponse::error($e->getMessage(), 400);
        }
    }

    public function index()
    {
        try {
            $dependence = Dependence::where("active", 1)->get();
            return ApiResponse::success($dependence, 'dependencias recuperadas con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar las dependencias', 500);
        }
    }
    public function destroy(Request $request)
    {
        try {
            $dependence = Dependence::find($request->id);

            if (!$dependence) {
                return ApiResponse::error("Dependencias no encontradas", 404);
            }

            $dependence->update(['active' => false]);
            return ApiResponse::success(null, 'Dependencia eliminada correctamente.');
        } catch (\Exception $e) {
            Log::error("error ". $e->getMessage(),[]);
            return ApiResponse::error('Error al eliminar la dependencia: ', 500);
        }
    }
}
