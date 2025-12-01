<?php

namespace App\Http\Controllers;

use App\Models\Penalty;
use App\Models\PenaltyPreloadData;
use App\Models\PenaltyView;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PenaltyPreloadDataController extends Controller
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
            $query->where('user_id', $user->id);
        }
        // Sistemas y administrativo no necesitan filtros

        $penalty_preload_data = $query->orderBy('id', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'success' => true,
            'data' => $penalty_preload_data,
            'user_role' => $userRole
        ]);
    }

    /**
     * Crear o actualizar la información pre-cargada de la multa.
     * Si se recibe un 'id', actualiza; si no, crea una nueva.
     */
    public function storeOrUpdate(Request $request)
    {
        try {
            $data = $request->all();
            $penaltyPreloadData = null;

            $data = $this->convertBooleanStrings($data);

            if (isset($data['date'])) {
                $parts = explode('/', $data['date']);
                if (count($parts) === 3) {
                    $data['date'] = "{$parts[2]}-{$parts[1]}-{$parts[0]}";
                }
            }

            if (!empty($data['penalty_preload_data_id']) && intval($data['penalty_preload_data_id']) > 0) {
                unset($data['id']); // prevenir cambios de ID

                unset($data['user_id']);
                $penaltyPreloadData = PenaltyPreloadData::findOrFail($data['penalty_preload_data_id']);
                $penaltyPreloadData->update($data);
            } else {
                unset($data['id']);
                $data['user_id'] = Auth::id();
                $penaltyPreloadData = PenaltyPreloadData::create($data);
            }

            // Devuelve solo el modelo, no un JsonResponse
            return $penaltyPreloadData;
        } catch (\Throwable $e) {
            \Log::error('Error en PenaltyPreloadDataController ~ storeOrUpdate: ' . $e->getMessage());
            throw $e; // Propaga la excepción
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



    /**
     * Eliminar datos pre-cargados
     */
    public function destroy($id)
    {
        $penalty = PenaltyPreloadData::findOrFail($id);
        $penalty->delete();

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Multa eliminada correctamente'
        // ]);
    }
}