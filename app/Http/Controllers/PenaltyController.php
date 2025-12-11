<?php

namespace App\Http\Controllers;

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
            // return $data;
            $data = $this->convertBooleanStrings($data);
         

            // REGISTRAR PENALTY PREALOAD DATA
            $penaltyPreloadDataController = new PenaltyPreloadDataController();
            $penaltyPreloadData = $penaltyPreloadDataController->storeOrUpdate($request);

            // Ahora $penaltyPreloadData es un modelo, no un JsonResponse
            $data["penalty_preload_data_id"] = $penaltyPreloadData->id;

            // ... resto del código para manejo de imágenes ...
            if ($request->hasFile('image_penaltie') && $request->file('image_penaltie')->isValid()) {
                $firma = $request->file('image_penaltie');
                $dirPath = "presidencia/SIVIC/multas";

                $imagePath = $this->ImgUpload(
                    $firma,
                    $request->curp,
                    $dirPath,
                    $request->curp
                );
$data['active']=true;
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
                unset($data['created_by']);
                Log::error("se actualizo la info de el item", []);

                $penalty = Penalty::findOrFail($data['id']);
                $penalty->update($data);
                $message = 'Multa actualizada correctamente';
                $statusCode = 200;
            } else {
                $data['created_by'] = Auth::id();
                unset($data['id']);
                $penalty = Penalty::create($data);
                $message = 'Multa creada correctamente';
                $statusCode = 201;
            }

            // $penaltyView = PenaltyView::find($penalty->id);

            return $penalty;
        } catch (\Throwable $e) {
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
