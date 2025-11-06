<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Log;

class LogController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Log::with('user');

            // 🔍 Filtro opcional por modelo
            if ($request->filled('modelo')) {
                $query->where('loggable_type', 'like', '%' . $request->modelo . '%');
            }

            // 🔍 Filtro opcional por acción
            if ($request->filled('accion')) {
                $query->where('action', $request->accion);
            }

            $logs = $query->latest()->get();

            // Traducción de acciones
            $traducciones = [
                'created'  => 'Creado',
                'updated'  => 'Actualizado',
                'deleted'  => 'Eliminado',
                'restored' => 'Restaurado',
                'saved'    => 'Guardado',
            ];
            $data = $logs->map(function ($log) use ($traducciones) {

                // Acción base (del observer)
                $accion = $traducciones[$log->action] ?? ucfirst($log->action);

                // Ajustar según el método HTTP
                switch (strtoupper($log->http_method)) {
                    case 'POST':
                        $accion = 'Creado';
                        break;
                    case 'PUT':
                    case 'PATCH':
                        $accion = 'Actualizado';
                        break;
                    case 'DELETE':
                        $accion = 'Desactivado';
                        break;
                }

                // Decodificar JSON
                $old = $this->safeJsonDecode($log->old_values);
                $new = $this->safeJsonDecode($log->new_values);

                // Eliminar password si existe
                if (is_array($old)) unset($old['password']);
                if (is_array($new)) unset($new['password']);

                return [
                    'id' => $log->id,
                    'usuario' => $log->user?->fullName ?? 'Sistema',
                    'modelo' => class_basename($log->loggable_type),
                    'accion' => $accion,
                    'valores_anteriores' => $old, // <-- usar $old filtrado
                    'valores_nuevos' => $new,     // <-- usar $new filtrado
                    'ip' => $log->ip_address,
                    'metodo_http' => $log->http_method,
                    'fecha' => $log->created_at->format('Y-m-d H:i:s'),
                ];
            });
            return response()->json([
                'success' => true,
                'message' => 'Historial de acciones obtenido correctamente',
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la bitácora: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Decodifica JSON solo si es string válido
     */
    private function safeJsonDecode($value)
    {
        if (is_null($value)) return null;
        if (is_array($value)) return $value;

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        return $value;
    }
}
