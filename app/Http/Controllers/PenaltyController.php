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
            // if ($userRole === 'director') {
            //     $query->where('user_dependence_id', $userDependenceId);
            // } elseif ($userRole === 'usuario') {
            //     $query->where('created_by', $user->id)->where('active', 1);
            // }
            // Sistemas y administrativo no necesitan filtros

            $penalties = $query->where("active",1)->orderBy('id', 'desc')->get();

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
            // DEPURACIÓN DETALLADA AL INICIO
            Log::info('=== INICIO DEPURACIÓN storeOrUpdate ===');
            Log::info('Content-Type header:', ['content-type' => $request->headers->get('Content-Type')]);
            Log::info('Método HTTP:', ['method' => $request->method()]);

            $data = $request->all();
            Log::info('Datos recibidos en storeOrUpdate:', $data);

            $data = $this->convertBooleanStrings($data);

            // REGISTRAR PENALTY PREALOAD DATA
            $penaltyPreloadDataController = new PenaltyPreloadDataController();
            $penaltyPreloadData = $penaltyPreloadDataController->storeOrUpdate($request);
            Log::info('PenaltyPreloadData creado/actualizado:', ['id' => $penaltyPreloadData->id]);

            $data["penalty_preload_data_id"] = $penaltyPreloadData->id;

            Log::info('=== VERIFICANDO ARCHIVOS ===');

            // FUNCIÓN PARA PROCESAR ARCHIVOS
            $processFile = function ($fieldName, $dirPath, $filenamePrefix = null) use ($request, &$data) {
                $fileDetected = false;
                $file = null;

                // Verifica de 3 maneras diferentes
                // 1. Usando $request->hasFile() (forma estándar)
                if ($request->hasFile($fieldName)) {
                    Log::info("Método 1: \$request->hasFile detectó {$fieldName}");
                    $file = $request->file($fieldName);
                    $fileDetected = true;
                }
                // 2. Verificando en $data
                else if (isset($data[$fieldName]) && $data[$fieldName] instanceof \Illuminate\Http\UploadedFile) {
                    Log::info("Método 2: {$fieldName} encontrado como UploadedFile en \$data");
                    $file = $data[$fieldName];
                    $fileDetected = true;
                }
                // 3. Verificando en $request->allFiles()
                else if ($request->allFiles() && isset($request->allFiles()[$fieldName])) {
                    Log::info("Método 3: {$fieldName} encontrado en \$request->allFiles()");
                    $file = $request->allFiles()[$fieldName];
                    $fileDetected = true;
                }

                // Si se detectó el archivo, procesarlo
                if ($fileDetected && isset($file)) {
                    Log::info("Archivo {$fieldName} detectado:", [
                        'nombre' => $file->getClientOriginalName(),
                        'tamaño' => $file->getSize(),
                        'mime' => $file->getMimeType(),
                        'válido' => $file->isValid(),
                        'error' => $file->getError(),
                        'path_temporal' => $file->getPathname()
                    ]);

                    if ($file->isValid()) {
                        Log::info("Subiendo {$fieldName} a directorio: {$dirPath}");

                        // Asegurarse de que tenemos CURP
                        $curp = $request->curp ?? $data['curp'] ?? 'unknown_' . time();

                        // Usar prefijo personalizado si se proporciona
                        $filename = $filenamePrefix ? "{$filenamePrefix}_{$curp}" : $curp;

                        $imagePath = $this->ImgUpload(
                            $file,
                            $curp,
                            $dirPath,
                            $filename
                        );

                        Log::info("{$fieldName} subido exitosamente:", ['path' => $imagePath]);

                        // Store the complete URL in the data array
                        $fullUrl = "https://api.gpcenter.gomezpalacio.gob.mx/" . $dirPath . "/" . $curp . "/" . $imagePath;
                        $data[$fieldName] = $fullUrl;
                        Log::info("URL completa de {$fieldName}:", ['url' => $fullUrl]);

                        // Asegurarse de que el archivo temporal se elimine
                        if (file_exists($file->getPathname())) {
                            unlink($file->getPathname());
                        }

                        return true;
                    } else {
                        Log::warning("Archivo {$fieldName} no es válido", [
                            'error' => $file->getError(),
                            'error_message' => $file->getErrorMessage()
                        ]);
                        return false;
                    }
                } else {
                    Log::info("No se detectó archivo {$fieldName} en ningún método");

                    // Si hay una ruta temporal o objeto, eliminarlo del array
                    if (isset($data[$fieldName])) {
                        Log::info("Valor actual de {$fieldName} en data:", [
                            'type' => gettype($data[$fieldName]),
                            'value' => $data[$fieldName]
                        ]);

                        // Eliminar si es una ruta temporal o un objeto UploadedFile
                        if (is_string($data[$fieldName]) && str_contains($data[$fieldName], 'Temp\\php')) {
                            Log::info("Eliminando ruta temporal de {$fieldName}");
                            unset($data[$fieldName]);
                        } else if (is_array($data[$fieldName]) || is_object($data[$fieldName])) {
                            Log::info("Eliminando objeto/array de {$fieldName} que no es archivo válido");
                            unset($data[$fieldName]);
                        }
                    }
                    return false;
                }
            };

            // PROCESAR LOS 3 ARCHIVOS
            Log::info('--- Procesando image_penaltie ---');
            $processFile('image_penaltie', 'presidencia/SIVIC/multas');

            Log::info('--- Procesando images_evidences ---');
            $processFile('images_evidences', 'presidencia/SIVIC/evidences');

            Log::info('--- Procesando images_evidences_car ---');
            $processFile('images_evidences_car', 'presidencia/SIVIC/evidences', 'car');

            // Activar registro si se subió al menos un archivo
            if (isset($data['image_penaltie']) || isset($data['images_evidences']) || isset($data['images_evidences_car'])) {
                $data['active'] = true;
                Log::info('Registro activado porque hay al menos un archivo subido');
            }

            // Verificar datos antes de crear/actualizar
            Log::info('Datos finales antes de crear/actualizar Penalty:', $data);

            if (!empty($data['id']) && intval($data['id']) > 0) {
                Log::info('Actualizando Penalty existente:', ['id' => $data['id']]);
                unset($data['created_by']);

                $penalty = Penalty::findOrFail($data['id']);
                Log::info('Penalty encontrado para actualizar:', ['penalty' => $penalty->toArray()]);

                // Actualizar solo si tenemos cambios
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
            Log::info('=== FIN DEPURACIÓN storeOrUpdate ===');

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
    public function destroy($penalties_id)
    {
        
        $penalty = Penalty::where("id", $penalties_id);
        $penalty->delete();

        return response()->json([
            'success' => true,
            'message' => 'Multa eliminada correctamente'
        ]);
    }
}
