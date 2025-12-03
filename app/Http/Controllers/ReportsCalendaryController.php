<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Court;
use App\Models\Log;
use App\Models\PenaltyView;
use App\Models\Publicsecurities;
use App\Models\Traffic;
use Illuminate\Http\Request;

class ReportsCalendaryController extends Controller
{
    public function index()
    {
        try {

            $traffic = Traffic::where("active", 1)->get()
                ->map(fn($item) => $this->formatRecord($item, 'Transito Vialidad'));

            $penaltyView = PenaltyView::where("active", 1)->get()
                ->map(fn($item) => $this->formatRecord($item, 'Alcolimetro'));

            $publicsecurities = Publicsecurities::where("active", 1)->get()
                ->map(fn($item) => $this->formatRecord($item, 'Seguridad publica'));

            $court = Court::where("active", 1)->get()
                ->map(fn($item) => $this->formatRecord($item, 'Juzgados'));

            $result = $traffic
                ->merge($penaltyView)
                ->merge($publicsecurities)
                ->merge($court);

            return ApiResponse::success($result, 'Registros combinados recuperados correctamente');
        } catch (\Exception $e) {
            Log::error("Error en ReportsCalendaryController::index: " . $e->getMessage());
            return ApiResponse::error('Error al recuperar los registros', 500);
        }
    }

    /**
     * Convierte date + time en datetime o usa created_at
     */
    private function formatRecord($item, string $module)
    {
        $datetime = null;

        // 1. Si existe date + time → unir
        if (!empty($item->date) && !empty($item->time)) {
            $datetime = "{$item->date} {$item->time}";
        }

        // 2. Si no existe y hay created_at → usarlo
        if (!$datetime && $item->created_at) {
            $datetime = $item->created_at->format("Y-m-d H:i:s");
        }

        // 3. Si sigue sin existir datetime → NO enviar date/time
        $base = [
            'id' => $item->id,
            'module' => $module,
            'data' => $item,
        ];

        // 4. Agregar solo si hay datetime válido
        if ($datetime) {
            $base['dateTime'] = $datetime;
            $base['date'] = date("Y-m-d", strtotime($datetime));
            $base['time'] = date("H:i:s", strtotime($datetime));
        }

        return $base;
    }
}
