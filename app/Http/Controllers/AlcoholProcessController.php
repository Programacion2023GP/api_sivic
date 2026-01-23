<?php

namespace App\Http\Controllers;

use App\Models\AlcoholCase;
use App\Models\AlcoholHistory;
use App\Models\AlcoholRangeRule;
use App\Models\ApiResponse;
use App\Models\Penalty;
use App\Models\PenaltyView;
use Illuminate\Support\Facades\Log;
use App\Models\Process;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class AlcoholProcessController extends Controller
{
    public function index()
    {
        try {
            $cases = AlcoholCase::with(['currentProcess'])
                ->active()
                ->orderBy('created_at', 'desc')
                ->get();

            return ApiResponse::success($cases, 'Casos de alcohol obtenidos exitosamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener los casos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Crear un nuevo caso de alcohol
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // 1. Crear o actualizar el caso básico
            if ($request->id == 0) {
                // CREAR NUEVO CASO
                $case = AlcoholCase::create([
                    'alcohol_level' => $request->alcohol_concentration,
                    'active' => true,
                    'name' => $request->name,
                    'city' => $request->city,
                    'cp' => $request->cp,
                    'oficial_payroll' => $request->oficial_payroll,
                    'person_oficial' => $request->person_oficial,
                    'plate_number' => $request->plate_number,
                    'age' => $request->age,
                    'long' => $request->long,
                    'lat' => $request->lat,
                    'date' => $request->date,
                    'time' => $request->time,
                    "residence_folio" => null,
                    'requires_confirmation' => false
                ]);
            } else {
                // ACTUALIZAR CASO EXISTENTE
                $case = AlcoholCase::find($request->id);

                if ($case) {
                    $case->update([
                        'alcohol_level' => $request->alcohol_concentration ?? $case->alcohol_level,
                        'name' => $request->name ?? $case->name,
                        'city' => $request->city ?? $case->city,
                        'cp' => $request->cp ?? $case->cp,
                        'oficial_payroll' => $request->oficial_payroll ?? $case->oficial_payroll,
                        'person_oficial' => $request->person_oficial ?? $case->person_oficial,
                        'plate_number' => $request->plate_number ?? $case->plate_number,
                        'age' => $request->age ?? $case->age,
                        'lon' => $request->lon ?? $case->lon,
                        'lat' => $request->lat ?? $case->lat,
                        'date' => $request->date ?? $case->date,
                        'time' => $request->time ?? $case->time,
                    ]);
                } else {
                    DB::rollBack();
                    return ApiResponse::error('Caso no encontrado', 404);
                }
            }

            // 2. ACTUALIZAR LOS CASOS EXISTENTES para que apunten al NUEVO
            if ($request->residence_folio) {
                AlcoholCase::where('residence_folio', $request->residence_folio)
                    ->orWhere('id', $request->residence_folio)
                    ->update(['residence_folio' => $case->id]);
            }

            // 3. Buscar la regla para este nivel de alcohol
            $alcoholLevel = $request->alcohol_level ?? $request->alcohol_concentration; // Agregado fallback
            $rule = AlcoholRangeRule::where('active', true)
                ->where('min_value', '<=', $alcoholLevel)
                ->where(function ($query) use ($alcoholLevel) {
                    $query->where('max_value', '>=', $alcoholLevel)
                        ->orWhereNull('max_value');
                })
                ->first();

            if (!$rule) {
                DB::rollBack();
                return ApiResponse::error('No se encontró una regla para este nivel de alcohol: ' . $alcoholLevel, 400);
            }

            // 4. Obtener el primer proceso de la regla
            $firstProcess = $rule->processes()
                ->wherePivot('active', true)
                ->orderBy('orden')
                ->first();

            if (!$firstProcess) {
                DB::rollBack();
                return ApiResponse::error('La regla no tiene procesos configurados', 400);
            }

            // 5. Asignar el proceso al caso
            if ($request->id == 0) {
                $case->current_process_id = !empty($firstProcess->id)
                    ? $firstProcess->id
                    : 1;
            }
            $case->active = true;
            $case->save();

            // 6. Determinar qué modelo crear según el proceso
            $modelClass = $firstProcess->model_class;
            $dataProccess = null;
            $stepId = null;

            switch ($modelClass) {
                case 'App\\Models\\Penalty':
                case 'App\\Models\\Traffic':
                case 'App\\Models\\PublicSecurities':
                case 'App\\Models\\Court':
                    $controller = new PenaltyController();
                    if ($request->id > 0) {
                        // PARA ACTUALIZACIÓN: Encontrar el historial más reciente
                        $latestHistory = AlcoholHistory::where("case_id", $case->id)
                            ->orderByDesc('id')
                            ->first();

                        if ($latestHistory) {
                            $modifiedData = $request->all();
                            $modifiedData['id'] = $latestHistory->step_id;
                            $modifiedRequest = new Request($modifiedData);
                            $dataProccess = $controller->storeOrUpdate($modifiedRequest);
                            $stepId = $dataProccess->id;

                            // CREAR NUEVO REGISTRO EN EL HISTORIAL para la actualización

                        } else {
                            // Si no hay historial previo, crear uno nuevo
                            $dataProccess = $controller->storeOrUpdate($request);
                            $stepId = $dataProccess->id;
                        }
                    } else {
                        // PARA NUEVO REGISTRO
                        $dataProccess = $controller->storeOrUpdate($request);
                        $stepId = $dataProccess->id;

                        // CREAR REGISTRO EN EL HISTORIAL

                    }
                    break;

                default:
                    DB::rollBack();
                    return ApiResponse::error('Modelo no reconocido: ' . $modelClass, 400);
            }

            // Actualizar el ID del paso actual en el caso
            // Verificar si ESTE proceso YA existe para ESTE caso
            $penalty = PenaltyView::where('id', $case->id)->first();

            $processId = $penalty?->current_process_id ?? 1;

            $existsCurrent = AlcoholHistory::where("case_id", $case->id)
                ->where("process_id", $processId)  // ← Proceso ACTUAL, no anterior
                ->exists();

            $action = $existsCurrent ? "actualizacion" : "creacion";
            AlcoholHistory::create([
                "case_id"    => $case->id,
                "process_id" =>  $processId,
                "user_id"    => Auth::id(),
                "step_id"    => $dataProccess->id,
                "action"     => $action,
            ]);
            if ($stepId) {
                // $case->current_process_record_id = $stepId;
                $case->save();
            }

            DB::commit();
            return ApiResponse::success(
                $dataProccess,
                'Registrado correctamente'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Error al crear el caso', 500);
        }
    }

    /**
     * Mostrar un caso específico
     */
    public function show(Request $request)
    {
        try {
            $case = DB::selectOne('SELECT * FROM showPenalties WHERE cid = ?', [$request->cid]);

            if (!$case) {
                return ApiResponse::error('Caso no encontrado', 404);
            }

            return ApiResponse::success($case, 'Caso obtenido exitosamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener el caso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Actualizar un caso
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'alcohol_level' => 'numeric|min:0|max:99.99',
            'active' => 'boolean',
            'requires_confirmation' => 'boolean',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError('Error de validación');
        }

        try {
            $case = AlcoholCase::find($id);

            if (!$case) {
                return ApiResponse::error('Caso no encontrado', 404);
            }

            DB::beginTransaction();

            $case->update($request->only(['alcohol_level', 'active', 'requires_confirmation']));

            // Si se actualizó el nivel de alcohol, recalcular la regla aplicable
            if ($request->has('alcohol_level')) {
                $rule = AlcoholRangeRule::forAlcoholLevel($request->alcohol_level)->first();

                if ($rule) {
                    $firstProcess = $rule->processes()
                        ->wherePivot('active', true)
                        ->orderBy('orden')
                        ->first();

                    if ($firstProcess) {
                        $case->current_process_id = $firstProcess->id;
                        $case->save();
                    }
                }
            }

            DB::commit();

            return ApiResponse::success($case->load('currentProcess'), 'Caso actualizado exitosamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Error al actualizar el caso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Eliminar un caso
     */
    public function destroy($id)
    {
        try {
            $case = AlcoholCase::find($id);

            if (!$case) {
                return ApiResponse::error('Caso no encontrado', 404);
            }

            $case->delete();

            return ApiResponse::success(null, 'Caso eliminado exitosamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al eliminar el caso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Avanzar un caso al siguiente proceso
     */
    public function advance(Request $request)
    {
        try {
            DB::beginTransaction();

            // 1. Determinar el nivel de alcohol y encontrar la regla
            $alcoholLevel = $request->alcohol_concentration;
            $rule = AlcoholRangeRule::where('active', true)
                ->where('min_value', '<=', $alcoholLevel)
                ->where(function ($query) use ($alcoholLevel) {
                    $query->where('max_value', '>=', $alcoholLevel)
                        ->orWhereNull('max_value');
                })
                ->first();

            if (!$rule) {
                DB::rollBack();
                return ApiResponse::error('No se encontró una regla para este nivel de alcohol: ' . $alcoholLevel, 400);
            }

            // 2. Obtener el primer proceso de la regla
            $firstProcess = $rule->processes()
                ->wherePivot('active', true)
                ->orderBy('orden')
                ->first();

            if (!$firstProcess) {
                DB::rollBack();
                return ApiResponse::error('La regla no tiene procesos configurados', 400);
            }

            // 3. Crear o actualizar el caso
            if ($request->id == 0) {
                // CREAR NUEVO CASO
                $case = AlcoholCase::create([
                    'alcohol_level' => $request->alcohol_concentration,
                    'active' => true,
                    'name' => $request->name,
                    'city' => $request->city,
                    'cp' => $request->cp,
                    'oficial_payroll' => $request->oficial_payroll,
                    'person_oficial' => $request->person_oficial,
                    'plate_number' => $request->plate_number,
                    'age' => $request->age,
                    'long' => $request->long,
                    'lat' => $request->lat,
                    'date' => $request->date,
                    'time' => $request->time,
                    "residence_folio" => null,
                    'requires_confirmation' => false,
                    'current_process_id' => $firstProcess->id
                ]);

                // Actualizar casos existentes con residence_folio
                if ($request->residence_folio) {
                    AlcoholCase::where('residence_folio', $request->residence_folio)
                        ->orWhere('id', $request->residence_folio)
                        ->update(['residence_folio' => $case->id]);
                }
            } else {
                // ACTUALIZAR CASO EXISTENTE
                $case = AlcoholCase::find($request->id);

                if (!$case) {
                    DB::rollBack();
                    return ApiResponse::error('Caso no encontrado', 404);
                }

                $case->update([
                    'alcohol_level' => $request->alcohol_concentration ?? $case->alcohol_level,
                    'name' => $request->name ?? $case->name,
                    'city' => $request->city ?? $case->city,
                    'cp' => $request->cp ?? $case->cp,
                    'oficial_payroll' => $request->oficial_payroll ?? $case->oficial_payroll,
                    'person_oficial' => $request->person_oficial ?? $case->person_oficial,
                    'plate_number' => $request->plate_number ?? $case->plate_number,
                    'age' => $request->age ?? $case->age,
                    'long' => $request->long ?? $case->long,
                    'lat' => $request->lat ?? $case->lat,
                    'date' => $request->date ?? $case->date,
                    'time' => $request->time ?? $case->time,
                ]);
            }

            // 4. Determinar qué modelo crear según el proceso y crear el registro
            $modelClass = $firstProcess->model_class;
            $stepId = null; // Variable para almacenar el ID del registro creado

            switch ($modelClass) {
                case 'App\\Models\\Penalty':
                case 'App\\Models\\Traffic':
                case 'App\\Models\\PublicSecurities':
                case 'App\\Models\\Court':
                    $controller = new PenaltyController();
                    $response = null;

                    if ($request->id > 0) {
                        $latestHistory = AlcoholHistory::where("case_id", $case->id)
                            ->orderByDesc('id')
                            ->first();

                        // IMPORTANTE: Preservar archivos al crear nuevo Request
                        $modifiedData = $request->all();
                        $modifiedData['id'] = $latestHistory->step_id ?? 0;

                        // Crear nuevo Request preservando archivos
                        $modifiedRequest = new Request(
                            $modifiedData,
                            $request->query->all(),
                            $request->attributes->all(),
                            $request->cookies->all(),
                            $request->files->all(), // ¡Mantener archivos!
                            $request->server->all(),
                            $request->getContent()
                        );

                        // También copiar los archivos específicos
                        if ($request->hasFile('image_penaltie_money')) {
                            $modifiedRequest->files->set('image_penaltie_money', $request->file('image_penaltie_money'));
                        }
                        if ($request->hasFile('image_penaltie')) {
                            $modifiedRequest->files->set('image_penaltie', $request->file('image_penaltie'));
                        }
                        if ($request->hasFile('images_evidences')) {
                            $modifiedRequest->files->set('images_evidences', $request->file('images_evidences'));
                        }
                        if ($request->hasFile('images_evidences_car')) {
                            $modifiedRequest->files->set('images_evidences_car', $request->file('images_evidences_car'));
                        }

                        $response = $controller->storeOrUpdate($modifiedRequest);
                    } else {
                        $response = $controller->storeOrUpdate($request);
                    }

                    // Verificar respuesta y obtener el ID

                    if ($request->id > 0) {
                        // PARA ACTUALIZACIÓN: Encontrar el historial más reciente
                        $latestHistory = AlcoholHistory::where("case_id", $case->id)
                            ->orderByDesc('id')
                            ->first();

                        if ($latestHistory) {
                            $modifiedData = $request->all();
                            $modifiedData['id'] = $latestHistory->step_id;
                            $modifiedRequest = new Request($modifiedData);
                            $dataProccess = $controller->storeOrUpdate($modifiedRequest);
                            $stepId = $dataProccess->id;

                            // CREAR NUEVO REGISTRO EN EL HISTORIAL para la actualización

                        } else {
                            // Si no hay historial previo, crear uno nuevo
                            $dataProccess = $controller->storeOrUpdate($request);
                            $stepId = $dataProccess->id;
                        }
                    } else {
                        // PARA NUEVO REGISTRO
                        $dataProccess = $controller->storeOrUpdate($request);
                        $stepId = $dataProccess->id;

                        // CREAR REGISTRO EN EL HISTORIAL

                    }

                        if (!$stepId) {
                            DB::rollBack();
                            return ApiResponse::error('No se pudo obtener el ID del registro creado', 400);
                        }
                    
                    break;

                default:
                    DB::rollBack();
                    return ApiResponse::error('Modelo no reconocido: ' . $modelClass, 400);
            }

            // 5. Verificar que se obtuvo el ID
            if (!$stepId) {
                DB::rollBack();
                return ApiResponse::error('No se pudo obtener el ID del registro creado', 400);
            }

            // 6. Crear el registro en AlcoholHistory
            $existsCurrent = AlcoholHistory::where("case_id", $case->id)
                ->where("process_id", $firstProcess->id)
                ->exists();

            $action = $existsCurrent ? "actualizacion" : "creacion";

            AlcoholHistory::create([
                "case_id"    => $case->id,
                "process_id" => $firstProcess->id,
                "user_id"    => Auth::id(),
                "step_id"    => $stepId, // Usar el ID obtenido
                "action"     => $action,
            ]);

            // 7. Verificar si el caso está activo
            if (!$case->active) {
                DB::rollBack();
                return ApiResponse::error('El caso no está activo', 400);
            }

            // 8. Obtener todos los procesos activos de la regla ordenados
            $processes = $rule->processes()
                ->wherePivot('active', true)
                ->orderBy('orden')
                ->get();

            // Si no hay más procesos después del actual, marcar como completado
            $currentIndex = $processes->search(function ($process) use ($case) {
                return $process->id === $case->current_process_id;
            });

            if ($currentIndex === false) {
                DB::rollBack();
                return ApiResponse::error('El proceso actual no pertenece a la regla aplicable', 400);
            }

            // Si ya está en finish: true, solo procesar el paso actual sin avanzar
            if ($case->finish === true) {
                $case->save();
                DB::commit();
                return ApiResponse::success($case, 'Caso ya finalizado - Registro histórico creado');
            }

            // Si es el último proceso
            if ($currentIndex === $processes->count() - 1) {
                $case->finish = true;
                $case->save();
                DB::commit();
                return ApiResponse::success($case, 'Caso completado (último proceso alcanzado)');
            }

            // 9. Preparar para el siguiente proceso (solo si NO está finish)
            $case->requires_confirmation = true;
            $nextProcess = $processes[$currentIndex + 1];
            $case->current_process_id = $nextProcess->id;
            $case->save();

            DB::commit();
            return ApiResponse::success($case->load('currentProcess'), 'Caso avanzado al siguiente proceso');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("ERROR EN advance: " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return ApiResponse::error('Error al avanzar el caso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener casos por rango de alcohol
     */
    public function getByAlcoholRange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'min' => 'required|numeric|min:0',
            'max' => 'nullable|numeric|min:0|gt:min',
        ]);

        if ($validator->fails()) {
            return ApiResponse::validationError('Error de validación');
        }

        try {
            $cases = AlcoholCase::with(['currentProcess'])
                ->active()
                ->byAlcoholRange($request->min, $request->max)
                ->orderBy('alcohol_level', 'asc')
                ->get();

            return ApiResponse::success($cases, 'Casos filtrados por rango de alcohol');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al filtrar casos: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Obtener casos por proceso actual
     */
    public function getByProcess($processId)
    {
        try {
            $process = Process::find($processId);

            if (!$process) {
                return ApiResponse::error('Proceso no encontrado', 404);
            }

            $cases = AlcoholCase::with(['currentProcess'])
                ->active()
                ->inProcess($processId)
                ->orderBy('created_at', 'desc')
                ->get();

            return ApiResponse::success([
                'process' => $process,
                'cases' => $cases
            ], 'Casos obtenidos por proceso');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al obtener casos por proceso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Confirmar un caso que requiere confirmación
     */
    public function confirm($id)
    {
        try {
            $case = AlcoholCase::find($id);

            if (!$case) {
                return ApiResponse::error('Caso no encontrado', 404);
            }

            if (!$case->requires_confirmation) {
                return ApiResponse::error('El caso no requiere confirmación', 400);
            }

            $case->requires_confirmation = false;
            $case->save();

            return ApiResponse::success($case, 'Caso confirmado exitosamente');
        } catch (\Exception $e) {
            return ApiResponse::error('Error al confirmar el caso: ' . $e->getMessage(), 500);
        }
    }
}
