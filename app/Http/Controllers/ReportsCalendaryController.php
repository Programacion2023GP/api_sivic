<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Court;
use App\Models\Log;
use App\Models\PenaltyView;
use App\Models\Publicsecurities;
use App\Models\Traffic;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReportsCalendaryController extends Controller
{
    public function index()
    {
        try {
            $result = collect();

            // Tráfico
            $traffic = PenaltyView::where("active", 1)->where("current_process_id",1)->get();
            $this->addToCollection($result, $traffic, 'Transito Vialidad');

            // Alcolímetro
            $penaltyView = PenaltyView::where("active", 1)->where("current_process_id",2)->get();
            $this->addToCollection($result, $penaltyView, 'Alcolimetro');

            // Seguridad pública
            $publicsecurities = PenaltyView::where("active", 1)->where("current_process_id",3)->get();
            $this->addToCollection($result, $publicsecurities, 'Seguridad publica');

            // Juzgados
            $court = PenaltyView::where("active", 1)->where("current_process_id",4)->get();
            $this->addToCollection($result, $court, 'Juzgados');

            return ApiResponse::success($result->values()->all(), 'Registros combinados recuperados correctamente');
        } catch (\Exception $e) {
            Log::error("Error en ReportsCalendaryController::index: " . $e->getMessage());
            return ApiResponse::error('Error al recuperar los registros', 500);
        }
    }

    /**
     * Agrega registros a la colección principal
     */
    private function addToCollection(Collection &$collection, $items, string $module): void
    {
        foreach ($items as $item) {
            $formatted = $this->formatRecord($item, $module);
            if ($formatted) {
                $collection->push($formatted);
            }
        }
    }

    /**
     * Formatea un registro individual
     */
    private function formatRecord($item, string $module): ?array
    {
        try {
            $datetime = null;

            // 1. Si existe date + time → unir
            if ($item->date && $item->time) {
                $datetime = "{$item->date} {$item->time}";
            }

            // 2. Si no existe y hay created_at → usarlo
            if (!$datetime && $item->created_at) {
                $datetime = $item->created_at instanceof \DateTime
                    ? $item->created_at->format("Y-m-d H:i:s")
                    : (string) $item->created_at;
            }

            // 3. Si no hay datetime válido, omitir este registro
            if (!$datetime || strtotime($datetime) === false) {
                return null;
            }

            return [
                'id' => $item->id,
                'module' => $module,
                'data' => $item->toArray(),
                'dateTime' => $datetime,
                'date' => date("Y-m-d", strtotime($datetime)),
                'time' => date("H:i:s", strtotime($datetime)),
            ];
        } catch (\Exception $e) {
            Log::warning("Error formateando registro en ReportsCalendaryController: " . $e->getMessage());
            return null;
        }
    }
}
