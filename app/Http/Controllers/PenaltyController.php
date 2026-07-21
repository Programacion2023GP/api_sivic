<?php

namespace App\Http\Controllers;

use App\Models\HistoryPenalty;
use App\Models\Penalty;
use App\Models\PenaltyView;
use Exception;
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
           if ($userRole === 'usuario' &&  $userDependenceId == 3) {
                $query->where('created_by', $user->id)->where('active', 1);
            }
            // Sistemas y administrativo no necesitan filtros

            $penalties = $query->where("active", 1)->orderBy('id', 'desc')->get();

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

            // ---- Procesar PenaltyPreloadData ----
            $penaltyPreloadDataController = new PenaltyPreloadDataController();
            $penaltyPreloadData = $penaltyPreloadDataController->storeOrUpdate($request);
            $data["penalty_preload_data_id"] = $penaltyPreloadData->id;

            // Convertir boolean strings
            $data = $this->convertBooleanStrings($data);

            // Normalizar fecha
            if (isset($data['date'])) {
                $parts = explode('/', $data['date']);
                if (count($parts) === 3) {
                    $data['date'] = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
                }
            }

            // ---- Verificar si es actualización ----
            $isUpdate = !empty($data['penalties_id']) && intval($data['penalties_id']) > 0;

            // Si es actualización, obtener el registro existente
            if ($isUpdate) {
                $penalty = Penalty::findOrFail($data['penalties_id']);
            }

            // ---- Procesar imágenes (solo si hay archivo nuevo) ----

            // 1. image_penaltie_money
            if ($request->hasFile('image_penaltie_money') && $request->file('image_penaltie_money')->isValid()) {
                $data['image_penaltie_money'] = $this->handleImageUpload($request, $data, 'image_penaltie_money', 'cash');
            } elseif ($isUpdate && isset($penalty->image_penaltie_money)) {
                // Conservar la existente
                $data['image_penaltie_money'] = $penalty->image_penaltie_money;
            }

            // 2. images_evidences_car
            if ($request->hasFile('images_evidences_car') && $request->file('images_evidences_car')->isValid()) {
                $data['images_evidences_car'] = $this->handleImageUpload($request, $data, 'images_evidences_car', 'evidences', "car_{$request->curp}");
            } elseif ($isUpdate && isset($penalty->images_evidences_car)) {
                $data['images_evidences_car'] = $penalty->images_evidences_car;
            }

            // 3. image_penaltie
            if ($request->hasFile('image_penaltie') && $request->file('image_penaltie')->isValid()) {
                $data['image_penaltie'] = $this->handleImageUpload($request, $data, 'image_penaltie', 'multas');
            } elseif ($isUpdate && isset($penalty->image_penaltie)) {
                $data['image_penaltie'] = $penalty->image_penaltie;
            }

            // 4. images_evidences
            if ($request->hasFile('images_evidences') && $request->file('images_evidences')->isValid()) {
                $data['images_evidences'] = $this->handleImageUpload($request, $data, 'images_evidences', 'evidences');
            } elseif ($isUpdate && isset($penalty->images_evidences)) {
                $data['images_evidences'] = $penalty->images_evidences;
            }

            // ---- Crear o actualizar ----
            if ($isUpdate) {
                // Actualizar
                unset($data['created_by']);
                $penaltieId = $data['penalties_id'];
                unset($data['penalties_id']);

                $penalty->update($data);
                $message = 'Multa actualizada correctamente';
                $statusCode = 200;
            } else {
                // Crear nueva
                $data['created_by'] = Auth::id();
                unset($data['penalties_id']);
                unset($data['id']);

                $penalty = Penalty::create($data);
                $message = 'Multa creada correctamente';
                $statusCode = 201;
            }

            // ✅ Devolver el modelo
            return $penalty;
        } catch (\Exception $e) {
            \Log::error('❌ Error en storeOrUpdate: ' . $e->getMessage());
            throw $e;
        }
    }
    /**
     * 🔧 Procesa la subida de una imagen (soporta multipart y JSON serializado)
     * 
     * @param Request $request
     * @param array &$data       (por referencia para modificar)
     * @param string $fieldName  Nombre del campo (ej: 'image_penaltie')
     * @param string $subdirectory Subcarpeta dentro de presidencia/SIVIC (ej: 'multas')
     * @param string|null $customFilename Prefijo personalizado (ej: 'car_')
     * @return string|null       URL pública de la imagen o null
     */
    private function handleImageUpload(Request $request, array &$data, string $fieldName, string $subdirectory, ?string $customFilename = null)
    {
        $url = null;

        // Obtener CURP (desde request o desde data)
        $curp = $request->curp ?? $request->input('curp') ?? $data['curp'] ?? 'SIN_CURP';

        // Prefijo para el nombre del archivo
        $baseName = $customFilename ?? $curp;

        // Ruta base local
        $basePath = "presidencia/SIVIC/";
        $dirPath = rtrim($basePath . $subdirectory, '/');

        // ===== CASO 1: Archivo enviado por multipart/form-data =====
        if ($request->hasFile($fieldName) && $request->file($fieldName)->isValid()) {
            try {
                $file = $request->file($fieldName);

                // Subir localmente y obtener nombre del archivo
                $filename = $this->ImgUpload($file, $curp, $dirPath, $baseName);

                if ($filename) {
                    // Construir URL pública usando el helper asset() de Laravel
                    $url = asset("storage/{$dirPath}/{$curp}/{$filename}");
                    $data[$fieldName] = $url;
                    \Log::info("📸 Imagen {$fieldName} guardada en: {$url}");
                } else {
                    unset($data[$fieldName]);
                }
                return $url;
            } catch (\Exception $e) {
                \Log::error("❌ Error al subir {$fieldName}: " . $e->getMessage());
                unset($data[$fieldName]);
                return null;
            }
        }

        // ===== CASO 2: Archivo serializado en JSON (objeto o array) =====
        elseif (isset($data[$fieldName]) && (is_array($data[$fieldName]) || is_object($data[$fieldName]))) {
            try {
                $fileData = (array) $data[$fieldName];
                $tempFilePath = null;

                // Buscar la clave que contiene la ruta temporal
                foreach ($fileData as $key => $value) {
                    if (is_string($value) && (strpos($value, 'Temp') !== false || strpos($value, 'tmp') !== false)) {
                        $tempFilePath = $value;
                        break;
                    }
                }

                if ($tempFilePath && file_exists($tempFilePath)) {
                    // Crear objeto UploadedFile desde la ruta temporal
                    $file = new \Illuminate\Http\UploadedFile(
                        $tempFilePath,
                        basename($tempFilePath),
                        mime_content_type($tempFilePath) ?: 'application/octet-stream',
                        filesize($tempFilePath),
                        0,
                        true
                    );

                    $filename = $this->ImgUpload($file, $curp, $dirPath, $baseName);

                    if ($filename) {
                        $url = asset("storage/{$dirPath}/{$curp}/{$filename}");
                        $data[$fieldName] = $url;
                        \Log::info("📸 Imagen {$fieldName} (JSON) guardada en: {$url}");
                    } else {
                        unset($data[$fieldName]);
                    }
                } else {
                    // No se encontró archivo temporal, limpiar
                    unset($data[$fieldName]);
                }
                return $url;
            } catch (\Exception $e) {
                \Log::error("❌ Error al procesar {$fieldName} desde JSON: " . $e->getMessage());
                unset($data[$fieldName]);
                return null;
            }
        }

        // ===== CASO 3: Datos inválidos o ya procesados =====
        else {
            if (isset($data[$fieldName])) {
                $value = $data[$fieldName];

                // Si es una ruta temporal, eliminar
                if (is_string($value) && (str_contains($value, 'Temp') || str_contains($value, 'tmp'))) {
                    unset($data[$fieldName]);
                }
                // Si es un array/objeto vacío, eliminar
                elseif (is_array($value) && empty($value)) {
                    unset($data[$fieldName]);
                }
                // Si ya es una URL válida (del mismo servidor), mantenerla
                elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                    // Ya es URL, no hacemos nada
                }
                // Otros casos: eliminar
                else {
                    unset($data[$fieldName]);
                }
            }
            return null;
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


            $penalty = Penalty::findOrFail($request->penalties_id);

            // Solo proceder si el CURP es válido
            // if (!empty($request->curp) && trim($request->curp) !== '') {
            // Desactivar todas las multas con el mismo CURP (excluyendo null/vacíos)
            $updated = Penalty::where('id', $request->penalties_id)
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
