<?php

namespace App\Http\Controllers;

use App\Models\AlcoholRangeRule;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlcoholProcessController extends Controller
{
    public function process(Request $request)
    {
        $grado = (float) $request->grado;

        // 1️⃣ encontrar la regla
        $rule = DB::selectOne("
            SELECT *
            FROM alcohol_range_rules
            WHERE min_value <= ?
              AND (max_value >= ? OR max_value IS NULL)
            LIMIT 1
        ", [$grado, $grado]);

        if (!$rule) {
            return [
                "success" => false,
                "message" => "No existe una regla para el grado $grado"
            ];
        }

        // 2️⃣ traer procesos
        $processes = DB::select("
            SELECT p.*
            FROM alcohol_range_rules_process arp
            INNER JOIN processes p ON p.id = arp.process_id
            WHERE arp.rule_id = ?
            ORDER BY p.orden ASC
        ", [$rule->id]);

        return [
            "success" => true,
            "grado" => $grado,
            "rule" => $rule,
            "processes" => $processes
        ];
    }
    // ---------------------------
    // 3️⃣ Métodos de procesos
    // ---------------------------

    private function handlePenalty($grado)
    {
        // Lógica real de Penalty
        return "Penalty ejecutado (grado: $grado)";
    }

    private function handlePublicSecurity($grado)
    {
        // Lógica real de PublicSecurities
        return "Public Security ejecutado (grado: $grado)";
    }

    private function handleTraffic($grado)
    {
        // Lógica real de Traffic
        return "Traffic ejecutado (grado: $grado)";
    }

    private function handleCourt($grado)
    {
        // Lógica real de Court
        return "Court ejecutado (grado: $grado)";
    }
}
