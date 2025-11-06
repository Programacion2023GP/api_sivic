<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Techinical;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TechnicalController extends Controller
{
  public function createOrUpdate(Request $request)
{
    try {
        // Validación de datos
        $validated = $request->validate([
            'id' => 'nullable|integer',
            'procedureId' => 'required|integer|exists:procedure,id',
            'dependeceAssignedId' => 'required|integer|exists:dependence,id',
            'firstName' => 'required|string|max:255',
            'paternalSurname' => 'required|string|max:255',
            'maternalSurname' => 'nullable|string|max:255',
            'street' => 'required|string|max:255',
            'number' => 'required|integer',
            'city' => 'nullable|integer',
            'payroll' => 'required|integer',

                'section' => 'required|string|max:50',
            'postalCode' => 'required|integer',
            'municipality' => 'required|string|max:255',
            'locality' => 'required|string|max:255',
            'reference' => 'nullable|string|max:500',
            'cellphone' => 'required|string|max:20',
            'requestDescription' => 'required|string',
            'solutionDescription' => 'nullable|string',
            // 'active' => 'nullable|boolean'
        ]);

        // Buscar o crear registro
        $technical = $request->id && $request->id > 0
            ? Techinical::find($request->id)
            : new Techinical();

        if (!$technical && $request->id > 0) {
            return ApiResponse::error('Ficha técnica no encontrada', 404);
        }

        // Asignar campos desde la request
        $technical->procedureId = $validated['procedureId'];
            $technical->procedureId = $validated['payroll'];

            $technical->dependeceAssignedId = $validated['dependeceAssignedId'];
        $technical->userId = Auth::id(); // Más eficiente que Auth::user()->id

        // Datos personales
        $technical->firstName = trim($validated['firstName']);
        $technical->paternalSurname = trim($validated['paternalSurname']);
        $technical->maternalSurname = trim($validated['maternalSurname'] ?? '');

        // Dirección
        $technical->street = trim($validated['street']);
        $technical->number = $validated['number'];
        $technical->city = $validated['city'] ?? 0;
        $technical->section = trim($validated['section']);
        $technical->postalCode = $validated['postalCode'];
        $technical->municipality = trim($validated['municipality']);
        $technical->locality = $validated['locality'] ?? 0;
        $technical->reference = trim($validated['reference'] ?? '');

        // Contacto
        $technical->cellphone = trim($validated['cellphone']);

        // Descripciones
        $technical->requestDescription = trim($validated['requestDescription']);
        $technical->solutionDescription = trim($validated['solutionDescription'] ?? '');

        // Estado
        $technical->active = true;

        // Guardar
        $technical->save();

        $message = $request->id && $request->id > 0
            ? 'Ficha técnica actualizada correctamente'
            : 'Ficha técnica creada correctamente';

        return ApiResponse::success($technical, $message);

    }  catch (\Exception $e) {
        Log::error('Error en createOrUpdate Techinical: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);
        return ApiResponse::error('Error al guardar la ficha técnica: ' . $e->getMessage(), 500);
    }
}

    /**
     * Listar todas las fichas técnicas activas
     */
    public function index(Request $request)
    {
        try {
           $query = Techinical::where('active', true);

if ($request->user()->tokenCan('CapturistaFichas')) {
    $query->where("userId", Auth::id());
}

$technicals = $query->orderByDesc('id')->get();
            return ApiResponse::success($technicals, 'Fichas técnicas recuperadas con éxito');
        } catch (Exception $e) {
            Log::error('Error al recuperar fichas técnicas: ' . $e->getMessage());
            return ApiResponse::error('Error al recuperar las fichas técnicas', 500);
        }
    }
 public function report()
    {
        try {
            $technicals = DB::table('reports')->get();
            return ApiResponse::success($technicals, 'reportes con éxito');
        } catch (Exception $e) {
            Log::error('Error al recuperar fichas técnicas: ' . $e->getMessage());
            return ApiResponse::error('Error al recuperar los reportes', 500);
        }
    }
    /**
     * Borrar (desactivar) una ficha técnica
     */
    public function destroy(Request $request)
    {
        try {
            $technical = Techinical::find($request->id);

            if (!$technical) {
                return ApiResponse::error('Ficha técnica no encontrada', 404);
            }

            $technical->update(['active' => false]);

            return ApiResponse::success(null, 'Ficha técnica eliminada correctamente');
        } catch (Exception $e) {
            Log::error('Error al eliminar ficha técnica: ' . $e->getMessage());
            return ApiResponse::error('Error al eliminar la ficha técnica', 500);
        }
    }

}
