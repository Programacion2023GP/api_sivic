<?php

namespace App\Http\Controllers;

use App\Models\Court;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class CourtController extends Controller
{
    public function createOrUpdate(Request $request)
    {
        try {
            // Si el id existe (>0), buscamos el registro para actualizar
            $court = $request->id > 0
                ? Court::find($request->id)
                : new Court();

            if (!$court) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro no encontrado'
                ], 404);
            }

            // Rellenamos solo los campos permitidos ($fillable)
            $court->fill($request->only([
                'date',
                'referring_agency',
                'detainee_name',
                'detention_reason',
                'entry_time',
                'exit_datetime',
                'exit_reason',
                'fine_amount',
                'active',
            ]));
            $data = $request->all();

            if ($request->hasFile('image_court') && $request->file('image_court')->isValid()) {
                $firma = $request->file('image_court');
                $dirPath = "presidencia/SIVIC/cuourt/evidence";

                $imagePath = $this->ImgUpload(
                    $firma,
                    $request->date,
                    $dirPath,
                    $request->date
                );

                $court->image_court = "https://api.gpcenter.gomezpalacio.gob.mx/" .
                    $dirPath . "/" . $request->date . "/" . $imagePath;
            } else {
                // Si no hay archivo nuevo, eliminar la ruta temporal para no guardarla
                if (isset($data['image_court']) && str_contains($data['image_court'], 'Temp\\php')) {
                    unset($data['image_court']);
                }
            }

            // CORRECCIÓN: Asignar el ID del usuario a created_by
            $court->created_by = Auth::id();

            $court->save();

            $message = $request->id > 0
                ? 'Registro actualizado correctamente'
                : 'Registro creado correctamente';

            return response()->json([
                'success' => true,
                'data' => $court,
                'message' => $message
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status' => "error",
                'success' => false,
                'message' =>"ocurrio un error"
            ], 500);
        }
    }

    /**
     * Listar todos los registros activos
     */
    public function index()
    {
        try {
            $courts = Court::where('active', 1)->orderBy('id', 'desc')->get();
            return response()->json([
                'success' => true,
                'data' => $courts,
                'message' => 'Registros recuperados con éxito'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al recuperar los registros'
            ], 500);
        }
    }

    /**
     * "Eliminar" un registro (marcar como inactivo)
     */
    public function destroy(Request $request)
    {
        try {
            $court = Court::find($request->id);

            if (!$court) {
                return response()->json([
                    'success' => false,
                    'message' => 'Registro no encontrado'
                ], 404);
            }

            $court->update(['active' => false]);

            return response()->json([
                'success' => true,
                'data' => null,
                'message' => 'Registro eliminado correctamente'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el registro'
            ], 500);
        }
    }
}
