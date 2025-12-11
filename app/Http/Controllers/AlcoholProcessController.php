<?php

namespace App\Http\Controllers;

use App\Models\AlcoholCase;
use App\Models\AlcoholHistory;
use App\Models\AlcoholRangeRule;
use App\Models\ApiResponse;
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
        // 1. Validar
        $validator = Validator::make($request->all(), [
            'alcohol_level' => 'required|numeric|min:0|max:99.99',
        ]);

        // if ($validator->fails()) {
        //     return ApiResponse::validationError('Error de validación');
        // }

        try {
            DB::beginTransaction();

            // 2. Crear el caso básico
            // return $request->all();
            $case = AlcoholCase::create([
                'alcohol_level' => $request->alcohol_level,
                'active' => true,
                'name'=>$request->name,
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
                "residence_folio" => $request->residence_folio,
                'requires_confirmation' => false
            ]);

            // 3. Buscar la regla para este nivel de alcohol
            $alcoholLevel = $request->alcohol_level;
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
            $case->current_process_id = $firstProcess->id;
            $case->save();
            // 6. Determinar qué modelo crear según el proceso
            $modelClass = $firstProcess->model_class;
            // 7. Crear el registro en el modelo correspondiente
            $dataProccess = null;

            switch ($modelClass) {
                case 'App\\Models\\Penalty':
                    $controller = new PenaltyController();
                    $dataProccess = $controller->storeOrUpdate($request);
                    break;

                case 'App\\Models\\PublicSecurities':
                    // ...
                    break;

                case 'App\\Models\\Traffic':
                    // ...
                    break;

                case 'App\\Models\\Court':
                    // ...
                    break;

                default:
                    DB::rollBack();
                    return ApiResponse::error('Modelo no reconocido: ' . $modelClass, 400);
            }


            // 8. Guardar referencia al registro creado
            // $case->current_process_record_type = $modelClass;
            // $case->current_process_record_id = $record->id;
            $case->save();

            $requiresConfirmation = filter_var($request->requires_confirmation, FILTER_VALIDATE_BOOLEAN);
            if (intval($request->id) === 0 && !$requiresConfirmation) {
                Log::error("entramos",[]);
                AlcoholHistory::create([
                    "case_id"=> $case->id,
                    "process_id" => $firstProcess->pivot->process_id,
                    "user_id"=>Auth::id(),
                    "step_id" => $dataProccess->id,
                    
                ]);
            }
            DB::commit();
            return ApiResponse::success(
                $dataProccess
            , 'Registrado correctamente');
        } catch (\Exception $e) {
            DB::rollBack();
            return ApiResponse::error('Error al crear el caso: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Mostrar un caso específico
     */
    public function show($id)
    {
        try {
            $case = AlcoholCase::with(['currentProcess'])->find($id);

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
    public function advance($id)
    {
        try {
            DB::beginTransaction();

            $case = AlcoholCase::with(['currentProcess'])->find($id);

            if (!$case) {
                return ApiResponse::error('Caso no encontrado', 404);
            }

            if (!$case->active) {
                return ApiResponse::error('El caso no está activo', 400);
            }

            // Buscar la regla aplicable
            $rule = $case->applicableRule();

            if (!$rule) {
                return ApiResponse::error('No se encontró una regla aplicable para este nivel de alcohol', 400);
            }

            // Obtener todos los procesos activos de la regla ordenados
            $processes = $rule->processes()
                ->wherePivot('active', true)
                ->orderBy('orden')
                ->get();

            // Si no hay proceso actual, asignar el primero
            if (!$case->current_process_id) {
                if ($processes->isEmpty()) {
                    return ApiResponse::error('No hay procesos configurados para esta regla', 400);
                }

                $case->current_process_id = $processes->first()->id;
                $case->save();

                DB::commit();
                return ApiResponse::success($case->load('currentProcess'), 'Proceso inicial asignado');
            }

            // Buscar el proceso actual en la lista
            $currentIndex = $processes->search(function ($process) use ($case) {
                return $process->id === $case->current_process_id;
            });

            // Si no se encuentra el proceso actual
            if ($currentIndex === false) {
                return ApiResponse::error('El proceso actual no pertenece a la regla aplicable', 400);
            }

            // Si es el último proceso
            if ($currentIndex === $processes->count() - 1) {
                $case->active = false;
                $case->save();

                DB::commit();
                return ApiResponse::success($case, 'Caso completado (último proceso alcanzado)');
            }

            // Avanzar al siguiente proceso
            $nextProcess = $processes[$currentIndex + 1];
            $case->current_process_id = $nextProcess->id;
            $case->save();

            DB::commit();

            return ApiResponse::success($case->load('currentProcess'), 'Caso avanzado al siguiente proceso');
        } catch (\Exception $e) {
            DB::rollBack();
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
