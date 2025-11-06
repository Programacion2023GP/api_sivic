<?php

namespace App\Http\Controllers;

use App\Models\Penalty;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PenaltyController extends Controller
{
    /**
     * Listar multas activas
     */
    public function index()
    {
        // Subconsulta: IDs máximos por CURP
        $latestIds = Penalty::where('active', true)
            ->selectRaw('MAX(id) as id')
            ->groupBy('curp');

        // Consulta principal con JOIN a users
        $penalties = Penalty::join('users', 'penalties.created_by', '=', 'users.id')
            ->whereIn('penalties.id', $latestIds)
            ->where('penalties.active', 1)

            ->select('penalties.*', 'users.fullName as created_by')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $penalties
        ]);
    }
    public function historial(Request $request)
    {
        // Obtener el ID más alto del CURP (registro más reciente)
        $latestId = Penalty::where('active', true)
            ->where('curp', $request->curp)
            ->max('id');

        // Obtener todos los registros de ese CURP excluyendo el más reciente
        $history = Penalty::where('active', true)
            ->where('curp', $request->curp)
            ->where('id', '<>', $latestId)
            ->orderBy('id', 'desc') // opcional: ordenar del más reciente al más antiguo
            ->get();

        return response()->json([
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

            // 🖼️ Manejar subida de archivo (para CREACIÓN y ACTUALIZACIÓN)
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

            if (!empty($data['id']) && intval($data['id']) > 0) {
                // 🔄 Actualizar
                unset($data['created_by']); // No actualizar created_by

                $penalty = Penalty::findOrFail($data['id']);
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

            return response()->json([
                'status' => "success",
                'success' => true,
                'message' => $message,
                'data' => $penalty,
            ], $statusCode);
        } catch (\Throwable $e) {
            return response()->json([
                'status' => "error",
                'success' => false,
                'message' => $e->getMessage(),
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
            // Validar entrada
            $request->validate([
                'id' => 'required|integer|exists:penalties,id',
            ]);

            // Buscar la multa
            $updated = Penalty::where('curp', $request->curp)
                ->where('active', true)
                ->update(['active' => false]);

            return response()->json([
                'success' => true,
                'message' =>
                     '🚫 Multa desactivada correctamente.',
            ], 200);
        } catch (ModelNotFoundException $e) {
            // Si no se encuentra la multa
            return response()->json([
                'success' => false,
                'message' => '❌ La multa no existe o fue eliminada.',
            ], 404);
        } catch (\Throwable $e) {
            // Cualquier otro error inesperado
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
