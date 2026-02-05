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
            $penaltyPreloadDataController = new PenaltyPreloadDataController();
            $penaltyPreloadData = $penaltyPreloadDataController->storeOrUpdate($request);
            $data["penalty_preload_data_id"] = $penaltyPreloadData->id;
        // Convert boolean strings to actual booleans/integers
        $data = $this->convertBooleanStrings($data);

        // Normalizar fecha si viene en formato dd/mm/yyyy
        if (isset($data['date'])) {
            $parts = explode('/', $data['date']);
            if (count($parts) === 3) {
                // dd/mm/yyyy => yyyy-mm-dd
                $data['date'] = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
            }
        }

            // Procesar image_penaltie_money - CASO ESPECIAL
            // $image_penaltie_money = $this->handleImageUpload($request, $data, 'image_penaltie_money', 'cash');
            // $images_evidences_car =  $this->handleImageUpload($request, $data, 'images_evidences_car', 'evidences', "car_{$request->curp}");
            // $image_penaltie = $this->handleImageUpload($request, $data, 'image_penaltie', 'multas');
            // $images_evidences = $this->handleImageUpload($request, $data, 'images_evidences', 'evidences');
            // // if (!$images_evidences_car && $request->vehicle_brand) {

            // //     throw new Exception("Alguna imagen fallo por favor vuelva a intentarlo");
            // // }
            // // if (!$image_penaltie && $request->vehicle_brand) {

            // //     throw new Exception("Alguna imagen fallo por favor vuelva a intentarlo");
            // // }
            // // if (!$images_evidences && $request->vehicle_brand) {

            // //     throw new Exception("Alguna imagen fallo por favor vuelva a intentarlo");
            // // }
            //     $data['image_penaltie_money'] = $image_penaltie_money;
           
            //     $data['images_evidences_car'] = $images_evidences_car;
           
            //     $data['image_penaltie'] = $image_penaltie;
           
            //     $data['images_evidences'] = $images_evidences;
            

            // Log::info("data",$request->all());
            if (!empty($data['penalties_id']) && intval($data['penalties_id']) > 0) {
            // Actualizar
            // Log::info("se esta actualizando el penaltie");
            unset($data['created_by']);

            $penaltieId = $data['penalties_id'];
            unset($data['penalties_id']);

            $penalty = Penalty::findOrFail($penaltieId);
            $penalty->update($data);

            $message = 'Multa actualizada correctamente';
            $statusCode = 200;
        } else {
                Log::info("se esta creando el penaltie");

                // Crear nueva
                $data['created_by'] = Auth::id();
            unset($data['penalties_id']);
            unset($data['id']);

            $penalty = Penalty::create($data);
              
                // if (empty($data['image_penaltie_money'])) {
                //     throw new Exception('El campo person no puede estar vacío');
                // }
                $message = 'Multa creada correctamente';
            $statusCode = 201;
        }


        return $penalty;
    } catch (\Exception $e) {
       throw $e;
    }
}
    private function handleImageUpload(Request $request, array &$data, string $fieldName, string $subdirectory, ?string $customFilename = null)
    {
        $url = null;
        // VERIFICAR SI EL CAMPO CURP EXISTE
        if (!$request->has('curp') && empty($request->curp)) {

            // Intentar obtener curp de $data si no está en request
            $curp = $data['curp'] ?? $request->input('curp') ?? 'SIN_CURP';
        } else {
            $curp = $request->curp ?? $request->input('curp');
        }

        $filename = $customFilename ?? $curp;
        $baseUrl = "https://api.gpcenter.gomezpalacio.gob.mx/";
        $basePath = "presidencia/SIVIC/";

     

        // Debug mejorado
        $fileInfo = [
            'hasFile' => $request->hasFile($fieldName) ? 'SÍ' : 'NO',
            'existsInData' => isset($data[$fieldName]) ? 'SÍ' : 'NO'
        ];

        if ($request->hasFile($fieldName)) {
            $fileInfo['isValid'] = $request->file($fieldName)->isValid() ? 'SÍ' : 'NO';
            $fileInfo['type'] = get_class($request->file($fieldName));
        }


        // **CASO 1: Archivo normal (multipart/form-data)**
        if ($request->hasFile($fieldName) && $request->file($fieldName)->isValid()) {

            try {
                $file = $request->file($fieldName);
                $dirPath = rtrim($basePath . $subdirectory, '/');

               

                $imagePath = $this->ImgUpload($file, $curp, $dirPath, $filename);

                if ($imagePath) {
                    // Construir URL sin doble barra
                    $url = $baseUrl . $dirPath . "/" . $curp . "/" . $imagePath;
                    $data[$fieldName] = $url;

                    // IMPORTANTE: Eliminar el archivo temporal del array $data
                    if (isset($data[$fieldName]) && is_array($data[$fieldName])) {
                        unset($data[$fieldName]);
                    }

                } else {
                    unset($data[$fieldName]);
                }
                return $url;
            } catch (\Exception $e) {
                unset($data[$fieldName]);
            }
        }
        // **CASO 2: Archivo serializado en JSON (OBJETO)**
        elseif (isset($data[$fieldName]) && (is_array($data[$fieldName]) || is_object($data[$fieldName]))) {

            try {
                // Convertir a array si es objeto
                $fileData = (array) $data[$fieldName];

                // Buscar la clave con la ruta temporal (puede tener backslashes)
                $tempFilePath = null;
                foreach ($fileData as $key => $value) {
                    if (is_string($value) && (strpos($key, 'UploadedFile') !== false || strpos($value, 'Temp') !== false)) {
                        $tempFilePath = $value;
                        break;
                    }
                }

                if ($tempFilePath && file_exists($tempFilePath)) {
                   
                    $file = new \Illuminate\Http\UploadedFile(
                        $tempFilePath,
                        basename($tempFilePath),
                        @mime_content_type($tempFilePath),
                        filesize($tempFilePath),
                        0,
                        true
                    );

                    $dirPath = rtrim($basePath . $subdirectory, '/');
                    $imagePath = $this->ImgUpload($file, $curp, $dirPath, $filename);

                    if ($imagePath) {
                        $url = $baseUrl . $dirPath . "/" . $curp . "/" . $imagePath;
                        $data[$fieldName] = $url;
                    } else {
                        unset($data[$fieldName]);
                    }
                } else {
                  
                    unset($data[$fieldName]);
                }
            } catch (\Exception $e) {
                unset($data[$fieldName]);
            }
        }
        // **CASO 3: Limpieza de datos inválidos**
        else {

            if (isset($data[$fieldName])) {
                $value = $data[$fieldName];

                // Si es string con ruta temporal
                if (is_string($value) && (str_contains($value, 'Temp\\php') || str_contains($value, 'Temp/php'))) {
                    unset($data[$fieldName]);
                }
                // Si es array/objeto vacío
                elseif (is_array($value) && empty($value)) {
                    unset($data[$fieldName]);
                }
                // Si ya es una URL válida, mantenerla
                elseif (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                }
                // Otros casos
                else {
                  
                    unset($data[$fieldName]);
                }
            } else {
            }
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
