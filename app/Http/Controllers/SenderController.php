<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Sender;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SenderController extends Controller
{
    public function createorUpdate(Request $request)
    {
        try {
            // Si el IDDetalleTipo existe (>0), buscamos el registro para actualizar
            $sender = $request->id > 0
                ? Sender::find($request->id)
                : new Sender();

            if (!$sender) {
                return ApiResponse::error('Remitentes no encontrados', 404);
            }

            // Rellenamos solo los campos permitidos

            $sender->name = $request->name;
            $sender->active = $request->active;

            $sender->save();

            $message = $request->id > 0
                ? 'remitente  actualizado'
                : 'remitente  creado';

            return ApiResponse::success($sender, $message);
        } catch (Exception $e) {
            return ApiResponse::error('ocurrio un error', 500);
        }
    }

    public function index()
    {
        try {
            $sender = Sender::where("active", 1)->get();
            return ApiResponse::success($sender, 'remitentes recuperados con éxito');
        } catch (Exception $e) {
            return ApiResponse::error('Error al recuperar los doctores', 500);
        }
    }
    public function destroy(Request $request)
    {
        try {
            $sender = Sender::find($request->id);

            if (!$sender) {
                return ApiResponse::error("remitente no encontradas", 404);
            }

            $sender->update(['active' => false]);
            return ApiResponse::success(null, 'remitente eliminado correctamente.');
        } catch (\Exception $e) {
            Log::error("error " . $e->getMessage(), []);
            return ApiResponse::error('Error al eliminar la remitente: ', 500);
        }
    }
}
