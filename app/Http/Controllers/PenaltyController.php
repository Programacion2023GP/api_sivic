<?php

namespace App\Http\Controllers;

use App\Models\HistoryPenalty;
use App\Models\Penalty;
use App\Models\PenaltyView;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

use function Symfony\Component\Clock\now;

class PenaltyController extends Controller
{
    /**
     * Listar multas activas
     */
    public function index(Request $request)
    {
      try {
            $user = auth()->user();
            $userRole = $user->role;
            $userDependenceId = $user->dependence_id;

            $query = PenaltyView::query();

            // Aplicar filtros según el rol
            if ($userRole === 'director') {
                $query->where('user_dependence_id', $userDependenceId);
            } elseif ($userRole === 'usuario') {
                $query->where('created_by', $user->id)->where('active', 1);
            }
            // Sistemas y administrativo no necesitan filtros

            $penalties = $query->orderBy('id', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'data' => $penalties,
                'user_role' => $userRole
            ]);
      } catch (\Error $e) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Ocurrió un error al cambiar el estado de la multa.',
                'error' => $e->getMessage(),
            ], 500);
      }
    }
    public function courts(Request $request)
    {

        $penalties = DB::select("
            SELECT *
            FROM penalties_latest_view p
            WHERE p.alcohol_concentration >= 3
            AND NOT EXISTS (
                SELECT 1
                FROM courts c
                WHERE c.penalties_id = p.id
            )
            ORDER BY p.id DESC
        ");


        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $penalties,
        ]);
    }
    public function historial(int $id)
    {
        
        try {

            $query = HistoryPenalty::query()->where('residence_folio', $id);


            $penalties = $query->orderBy('id', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'success' => true,
                'data' => $penalties,
            ]);
        } catch (\Error $e) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Ocurrió un error al cambiar el estado de la multa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Crear o actualizar una multa.
     * Si se recibe un 'id', actualiza; si no, crea una nueva.
     */
    public function storeOrUpdate(Request $request)
    {
        try {
            $data = $request->all();
            Log::info('Datos recibidos en storeOrUpdate:', $data);

            $data = $this->convertBooleanStrings($data);

            // REGISTRAR PENALTY PREALOAD DATA
            $penaltyPreloadDataController = new PenaltyPreloadDataController();
            $penaltyPreloadData = $penaltyPreloadDataController->storeOrUpdate($request);
            Log::info('PenaltyPreloadData creado/actualizado:', ['id' => $penaltyPreloadData->id]);

            // Ahora $penaltyPreloadData es un modelo, no un JsonResponse
            $data["penalty_preload_data_id"] = $penaltyPreloadData->id;

            // Verificar si hay archivos en la solicitud
            Log::info('Verificación de archivos en la solicitud:');
            Log::info('image_penaltie presente:', [
                'hasFile' => $request->hasFile('image_penaltie'),
                'allFiles' => $request->allFiles(), // Ver TODOS los archivos recibidos
                'allInput' => $request->all() // Ver TODOS los datos recibidos
            ]);

            // También verifica si viene como base64 o binary
            Log::info('Headers:', ['headers' => $request->headers->all()]);
            Log::info('Content-Type:', ['type' => $request->headers->get('Content-Type')]);

            if ($request->hasFile('image_penaltie')) {
                $file = $request->file('image_penaltie');
                Log::info('Archivo image_penaltie detectado:', [
                    'nombre' => $file->getClientOriginalName(),
                    'tamaño' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'válido' => $file->isValid()
                ]);

                if ($file->isValid()) {
                    $dirPath = "presidencia/SIVIC/multas";
                    Log::info('Subiendo image_penaltie a directorio: ' . $dirPath);

                    $imagePath = $this->ImgUpload(
                        $file,
                        $request->curp,
                        $dirPath,
                        $request->curp
                    );

                    Log::info('image_penaltie subido exitosamente:', ['path' => $imagePath]);
                    $data['active'] = true;

                    // Store the complete URL in the data array
                    $fullUrl = "https://api.gpcenter.gomezpalacio.gob.mx/" . $dirPath . "/" . $request->curp . "/" . $imagePath;
                    $data['image_penaltie'] = $fullUrl;
                    Log::info('URL completa de image_penaltie:', ['url' => $fullUrl]);
                } else {
                    Log::warning('Archivo image_penaltie no es válido');
                }
            } else {
                Log::info('No se detectó archivo image_penaltie en la solicitud');
                // Si no hay archivo nuevo, eliminar la ruta temporal para no guardarla
                if (isset($data['image_penaltie'])) {
                    Log::info('Valor actual de image_penaltie en data:', ['value' => $data['image_penaltie']]);
                    if (str_contains($data['image_penaltie'], 'Temp\\php')) {
                        Log::info('Eliminando ruta temporal de image_penaltie');
                        unset($data['image_penaltie']);
                    }
                }
            }

            if ($request->hasFile('images_evidences')) {
                $file = $request->file('images_evidences');
                Log::info('Archivo images_evidences detectado:', [
                    'nombre' => $file->getClientOriginalName(),
                    'tamaño' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'válido' => $file->isValid()
                ]);

                if ($file->isValid()) {
                    $dirPath = "presidencia/SIVIC/evidences";
                    Log::info('Subiendo images_evidences a directorio: ' . $dirPath);

                    $imagePath = $this->ImgUpload(
                        $file,
                        $request->curp,
                        $dirPath,
                        $request->curp
                    );

                    Log::info('images_evidences subido exitosamente:', ['path' => $imagePath]);

                    // Store the complete URL in the data array
                    $fullUrl = "https://api.gpcenter.gomezpalacio.gob.mx/" . $dirPath . "/" . $request->curp . "/" . $imagePath;
                    $data['images_evidences'] = $fullUrl;
                    Log::info('URL completa de images_evidences:', ['url' => $fullUrl]);
                } else {
                    Log::warning('Archivo images_evidences no es válido');
                }
            } else {
                Log::info('No se detectó archivo images_evidences en la solicitud');
                // Si no hay archivo nuevo, eliminar la ruta temporal para no guardarla
                if (isset($data['images_evidences'])) {
                    Log::info('Valor actual de images_evidences en data:', ['value' => $data['images_evidences']]);
                    if (str_contains($data['images_evidences'], 'Temp\\php')) {
                        Log::info('Eliminando ruta temporal de images_evidences');
                        unset($data['images_evidences']);
                    }
                }
            }

            if ($request->hasFile('images_evidences_car')) {
                $file = $request->file('images_evidences_car');
                Log::info('Archivo images_evidences_car detectado:', [
                    'nombre' => $file->getClientOriginalName(),
                    'tamaño' => $file->getSize(),
                    'mime' => $file->getMimeType(),
                    'válido' => $file->isValid()
                ]);

                if ($file->isValid()) {
                    $dirPath = "presidencia/SIVIC/evidences";
                    Log::info('Subiendo images_evidences_car a directorio: ' . $dirPath);

                    $imagePath = $this->ImgUpload(
                        $file,
                        $request->curp,
                        $dirPath,
                        "car_$request->curp"
                    );

                    Log::info('images_evidences_car subido exitosamente:', ['path' => $imagePath]);

                    // Store the complete URL in the data array
                    $fullUrl = "https://api.gpcenter.gomezpalacio.gob.mx/" . $dirPath . "/" . $request->curp . "/" . $imagePath;
                    $data['images_evidences_car'] = $fullUrl;
                    Log::info('URL completa de images_evidences_car:', ['url' => $fullUrl]);
                } else {
                    Log::warning('Archivo images_evidences_car no es válido');
                }
            } else {
                Log::info('No se detectó archivo images_evidences_car en la solicitud');
                // Si no hay archivo nuevo, eliminar la ruta temporal para no guardarla
                if (isset($data['images_evidences_car'])) {
                    Log::info('Valor actual de images_evidences_car en data:', ['value' => $data['images_evidences_car']]);
                    if (str_contains($data['images_evidences_car'], 'Temp\\php')) {
                        Log::info('Eliminando ruta temporal de images_evidences_car');
                        unset($data['images_evidences_car']);
                    }
                }
            }

            // Verificar datos antes de crear/actualizar
            Log::info('Datos finales antes de crear/actualizar Penalty:', $data);

            if (!empty($data['id']) && intval($data['id']) > 0) {
                Log::info('Actualizando Penalty existente:', ['id' => $data['id']]);
                unset($data['created_by']);

                $penalty = Penalty::findOrFail($data['id']);
                Log::info('Penalty encontrado para actualizar:', ['penalty' => $penalty->toArray()]);

                $penalty->update($data);
                $message = 'Multa actualizada correctamente';
                $statusCode = 200;
                Log::info($message, ['id' => $penalty->id]);
            } else {
                Log::info('Creando nuevo Penalty');
                $data['created_by'] = Auth::id();
                unset($data['id']);

                $penalty = Penalty::create($data);
                $message = 'Multa creada correctamente';
                $statusCode = 201;
                Log::info($message, ['id' => $penalty->id]);
            }

            Log::info('Resultado final:', ['penalty_id' => $penalty->id, 'status' => $statusCode]);
            return $penalty;
        } catch (\Throwable $e) {
            Log::error('Error en storeOrUpdate:', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'línea' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Convert boolean strings to actual boolean values
     */
    private function convertBooleanStrings($data)
    {
        $booleanFields = [
            'active'
            // Add other boolean fields here if you have them
        ];

        foreach ($booleanFields as $field) {
            if (isset($data[$field])) {
                if ($data[$field] === 'true' || $data[$field] === true) {
                    $data[$field] = 1;
                } elseif ($data[$field] === 'false' || $data[$field] === false) {
                    $data[$field] = 0;
                }
            }
        }

        return $data;
    }

   


    public function toggleActive(Request $request)
    {
        try {


            $penalty = Penalty::findOrFail($request->id);

            // Solo proceder si el CURP es válido
            // if (!empty($request->curp) && trim($request->curp) !== '') {
            // Desactivar todas las multas con el mismo CURP (excluyendo null/vacíos)
            $updated = Penalty::where('id', $request->id)
                ->update(['active' => DB::raw('NOT active')]);
            return response()->json([
                'success' => true,
                'message' => '🚫 Multas desactivada correctamente.',
                'affected_records' => $updated
            ], 200);
            // }


        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => '❌ La multa no existe o fue eliminada.',
            ], 404);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => '⚠️ Ocurrió un error al cambiar el estado de la multa.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Eliminar multa
     */
    public function destroy($id)
    {
        $penalty = Penalty::findOrFail($id);
        $penalty->delete();

        return response()->json([
            'success' => true,
            'message' => 'Multa eliminada correctamente'
        ]);
    }
}
