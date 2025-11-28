<?php

namespace App\Http\Controllers;

use App\Models\Penalty;
use App\Models\PenaltyView;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenaltyController extends Controller
{
    /**
     * Listar multas activas
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $userRole = $user->role;
        $userDependenceId = $user->dependence_id;

        $query = PenaltyView::query();

        // Aplicar filtros según el rol
        if ($userRole === 'director') {
            $query->where('user_dependence_id', $userDependenceId);
        } elseif ($userRole === 'usuario') {
            $query->where('created_by', $user->id)->where('active',1);
        }
        // Sistemas y administrativo no necesitan filtros

        $penalties = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $penalties,
            'user_role' => $userRole
        ]);
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
    public function historial(Request $request)
    {
        $user = auth()->user();
        $userRole = $user->role;
        $userDependenceId = $user->dependence_id;

        $query = PenaltyView::where('active', true)
            ->where('curp', $request->curp);

        // Aplicar filtros según el rol
        if ($userRole === 'director') {
            $query->where('dependence_id', $userDependenceId);
        } elseif ($userRole === 'usuario') {
            $query->where('created_by', $user->id);
        }
        // Sistemas y administrativo no necesitan filtros

        $latestId = $query->max('id');

        $history = $query->clone()
            ->where('id', '<>', $latestId)
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'curp' => $request->curp,
            'history' => $history
        ]);
    }

    /**
     * Crear o actualizar una multa.
     * Si se recibe un 'id', actualiza; si no, crea una nueva.
     */
    public function storeOrUpdate(Request $request)
    {
        try {
            $data = $request->all();


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


            // REGISTRAR PENALTY PREALOAD DATA
            $penaltyPreloadData = new PenaltyPreloadDataController();
            $penaltyPreloadData = $penaltyPreloadData->storeOrUpdate($request);
            $data["penalty_preload_data_id"] = $penaltyPreloadData["id"];


            if ($request->hasFile('image_penaltie') && $request->file('image_penaltie')->isValid()) {
                $firma = $request->file('image_penaltie');
                $dirPath = "presidencia/SIVIC/multas";

                $imagePath = $this->ImgUpload(
                    $firma,
                    $request->curp,
                    $dirPath,
                    $request->curp
                );

                // Store the complete URL in the data array
                $data['image_penaltie'] = "https://api.gpcenter.gomezpalacio.gob.mx/" . $dirPath . "/" . $request->curp . "/" . $imagePath;
            } else {
                // Si no hay archivo nuevo, eliminar la ruta temporal para no guardarla
                if (isset($data['image_penaltie']) && str_contains($data['image_penaltie'], 'Temp\\php')) {
                    unset($data['image_penaltie']);
                }
            }
            if ($request->hasFile('images_evidences') && $request->file('images_evidences')->isValid()) {
                $firma = $request->file('images_evidences');
                $dirPath = "presidencia/SIVIC/evidences";

                $imagePath = $this->ImgUpload(
                    $firma,
                    $request->curp,
                    $dirPath,
                    $request->curp
                );

                // Store the complete URL in the data array
                $data['images_evidences'] = "https://api.gpcenter.gomezpalacio.gob.mx/" . $dirPath . "/" . $request->curp . "/" . $imagePath;
            } else {
                // Si no hay archivo nuevo, eliminar la ruta temporal para no guardarla
                if (isset($data['images_evidences']) && str_contains($data['images_evidences'], 'Temp\\php')) {
                    unset($data['images_evidences']);
                }
            }

            if ($request->hasFile('images_evidences_car') && $request->file('images_evidences_car')->isValid()) {
                $firma = $request->file('images_evidences_car');
                $dirPath = "presidencia/SIVIC/evidences";

                $imagePath = $this->ImgUpload(
                    $firma,
                    $request->curp,
                    $dirPath,
                    "car_$request->curp"
                );

                // Store the complete URL in the data array
                $data['images_evidences_car'] = "https://api.gpcenter.gomezpalacio.gob.mx/" . $dirPath . "/" . $request->curp . "/" . $imagePath;
            } else {
                // Si no hay archivo nuevo, eliminar la ruta temporal para no guardarla
                if (isset($data['images_evidences_car']) && str_contains($data['images_evidences_car'], 'Temp\\php')) {
                    unset($data['images_evidences_car']);
                }
            }

            if (!empty($data['id']) && intval($data['id']) > 0) {
                // 🔄 Actualizar
                unset($data['created_by']); // No actualizar created_by

                $penalty = Penalty::findOrFail($data['id']);
                // $penalty->penalty_preload_data= $penaltyPreloadData->id;
                $penalty->update($data);

                $message = 'Multa actualizada correctamente';
                $statusCode = 200;
            } else {
                // 🆕 Crear nueva
                $data['created_by'] = Auth::id();
                unset($data['id']); // eliminar si viene como 0

                $penalty = Penalty::create($data);
                $message = 'Multa creada correctamente';
                $statusCode = 201;
            }


            $penaltyView = PenaltyView::find($penalty->id);

            return response()->json([
                'status' => "success",
                'success' => true,
                'message' => $message,
                'data' => $penaltyView,
            ], $statusCode);
        } catch (\Throwable $e) {
            \Log::error('Error en PenaltyController ~ storeOrUpdate: ' . $e->getMessage());
            return response()->json([
                'status' => "error",
                'success' => false,
                'message' => "Ocurrio un error",
            ], 500);
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

    public function ImgUpload($image, $destination, $dir, $imgName)
    {
        // Verificar que la imagen sea válida
        if (!$image || !$image->isValid()) {
            throw new \Exception('La imagen no es válida');
        }

        // Generar nombre único para el archivo
        $extension = $image->getClientOriginalExtension();
        $filename = $imgName . '_' . time() . '.' . $extension;

        // Subir al microservicio con los parámetros específicos
        $imageUrl = $this->uploadToMicroservice($image, $destination, $dir, $filename);

        // Devolver la URL completa para la BD
        return $filename;
    }

    /**
     * Función auxiliar para subir al microservicio con los parámetros específicos
     */
    private function uploadToMicroservice($file, $destination, $dir, $filename)
    {
        try {
            $client = new \GuzzleHttp\Client([
                'verify' => false, // Disable SSL verification
            ]);

            $response = $client->request('POST', 'https://api.gpcenter.gomezpalacio.gob.mx/api/smImgUpload', [
                'multipart' => [
                    [
                        'name'     => 'Firma_Director',
                        'contents' => fopen($file->getPathname(), 'r'),
                        'filename' => $filename,
                    ],
                    [
                        'name' => 'dirDestination',
                        'contents' => $destination,
                    ],
                    [
                        'name' => 'dirPath',
                        'contents' => $dir,
                    ],
                    [
                        'name' => 'imgName',
                        'contents' => $filename,
                    ],
                    [
                        'name' => 'requestFileName',
                        'contents' => 'Firma_Director',
                    ],
                ],
                'timeout' => 30, // Add timeout
                'connect_timeout' => 10,
            ]);

            // Check response status
            if ($response->getStatusCode() !== 200) {
                throw new \Exception('Error al subir la imagen: ' . $response->getBody());
            }

            return $response;
        } catch (\Exception $e) {
            throw new \Exception('Error en uploadToMicroservice: ' . $e->getMessage());
        }
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
