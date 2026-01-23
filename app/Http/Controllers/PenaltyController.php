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
        if ($request->hasFile('image_penaltie_money') && $request->file('image_penaltie_money')->isValid()) {
            $file = $request->file('image_penaltie_money');
            $dirPath = "presidencia/SIVIC/cash";

            $imagePath = $this->ImgUpload(
                $file,
                $request->curp,
                $dirPath,
                $request->curp
            );

            $data['image_penaltie_money'] = "https://api.gpcenter.gomezpalacio.gob.mx/" .
                $dirPath . "/" . $request->curp . "/" . $imagePath;
        } 
        // CASO ESPECIAL: Cuando viene serializado como objeto en JSON
        elseif (isset($data['image_penaltie_money']) && is_array($data['image_penaltie_money'])) {
            
            // Extraer la ruta del archivo temporal del objeto serializado
            if (isset($data['image_penaltie_money']['Illuminate\Http\UploadedFile'])) {
                $tempFilePath = $data['image_penaltie_money']['Illuminate\Http\UploadedFile'];
                
                // Verificar si el archivo temporal existe
                if (file_exists($tempFilePath)) {
                    // Crear un objeto UploadedFile manualmente
                    $file = new \Illuminate\Http\UploadedFile(
                        $tempFilePath,
                        basename($tempFilePath),
                        mime_content_type($tempFilePath),
                        filesize($tempFilePath),
                        0, // error
                        true // test mode
                    );
                    
                    $dirPath = "presidencia/SIVIC/cash";
                    $imagePath = $this->ImgUpload(
                        $file,
                        $request->curp,
                        $dirPath,
                        $request->curp
                    );
                    
                    $data['image_penaltie_money'] = "https://api.gpcenter.gomezpalacio.gob.mx/" .
                        $dirPath . "/" . $request->curp . "/" . $imagePath;
                    
                } else {
                    unset($data['image_penaltie_money']);
                }
            } else {
                unset($data['image_penaltie_money']);
            }
        } else {
            if (isset($data['image_penaltie_money']) && str_contains($data['image_penaltie_money'], 'Temp\\php')) {
                unset($data['image_penaltie_money']);
            }
        }

        // Procesar los otros archivos (mantén tu lógica actual)
        // ... [tu código existente para los otros archivos]


        if (!empty($data['penaltie_id']) && intval($data['penaltie_id']) > 0) {
            // Actualizar
            unset($data['created_by']);

            $penaltieId = $data['penaltie_id'];
            unset($data['penaltie_id']);

            $penalty = Penalty::findOrFail($penaltieId);
            $penalty->update($data);

            $message = 'Multa actualizada correctamente';
            $statusCode = 200;
        } else {
            // Crear nueva
            $data['created_by'] = Auth::id();
            unset($data['penaltie_id']);
            unset($data['id']);

            $penalty = Penalty::create($data);

            $message = 'Multa creada correctamente';
            $statusCode = 201;
        }


        return $penalty;
    } catch (\Exception $e) {
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
