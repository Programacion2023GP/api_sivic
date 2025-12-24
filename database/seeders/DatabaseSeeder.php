<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
            DB::table('user_permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

         $permissions = [
            'usuarios_crear',
            'usuarios_actualizar',
            'usuarios_eliminar',
            'multas_crear',
            "multas_actualizar",

            'multas_ver',
            'multas_exportar',
            'multas_historial',
            'multas_eliminar',
            'juzgados_crear',
            'juzgados_actualizar',
            'juzgados_eliminar',
            'juzgados_ver',
            'juzgados_exportar',
            'juzgados_historial',

            'transito_vialidad__crear',
            'transito_vialidad__actualizar',
            'transito_vialidad__eliminar',
            'transito_vialidad__ver',
            'transito_vialidad__exportar',
            'transito_vialidad__historial',

            'reports_dashboard',
            'catalogo_dependencia_ver',
            'catalogo_dependencia_crear',
            'catalogo_dependencia_actualizar',
            'catalogo_dependencia_eliminar',
            'catalogo_dependencia_exportar',
            'catalogo_doctor_ver',
            'catalogo_doctor_crear',
            'catalogo_doctor_actualizar',
            'catalogo_doctor_eliminar',
            'catalogo_doctor_exportar',
            'catalogo_motivo_detencion_ver',
            'catalogo_motivo_detencion_crear',
            'catalogo_motivo_detencion_actualizar',
            'catalogo_motivo_detencion_eliminar',
            'catalogo_motivo_detencion_exportar',
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'name' => $permission,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $departaments = [
            ['name' => 'Secretaría del Ayuntamiento', 'color' => '#1E90FF'],
            ['name' => 'Juzgados Cívico Municipales', 'color' => '#6A5ACD'],
            ['name' => 'Contraloría Municipal', 'color' => '#2E8B57'],
            ['name' => 'Dirección de Tecnologías de la Información', 'color' => '#00CED1'],
            ['name' => 'Tránsito y Vialidad', 'color' => '#FFD700'],
            ['name' => 'Dirección de Seguridad Pública', 'color' => '#B22222'],
        ];
        foreach ($departaments as $dep) {
            DB::table('dependences')->insert([
                'name' => $dep['name'],
                'color' => $dep['color'],
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Permisos creados en español y tabla reiniciada.');

        DB::table('users')->insert([
            [
                'id' => 1,
                'payroll' => 'admin',
                'dependence_id' => 1,
                'role' => 'sistemas',
                'firstName' => 'Admin',
                'paternalSurname' => 'Sistemas',
                'maternalSurname' => '',
                'password' => Hash::make('desarrollo'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'payroll' => 'dir002',
                'role' => 'director',
                'dependence_id' => 2,
                'firstName' => 'Roberto',
                'paternalSurname' => 'Director',
                'maternalSurname' => 'Depend2',
                'password' => Hash::make('password123'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'payroll' => 'adm003',
                'role' => 'administrativo',
                'dependence_id' => 3,
                'firstName' => 'Laura',
                'paternalSurname' => 'Admin',
                'maternalSurname' => 'Depend3',
                'password' => Hash::make('password123'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'payroll' => 'user004',
                'role' => 'usuario',
                'dependence_id' => 4,
                'firstName' => 'Miguel',
                'paternalSurname' => 'Usuario',
                'maternalSurname' => 'Depend4',
                'password' => Hash::make('password123'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'payroll' => 'dir005',
                'role' => 'director',
                'dependence_id' => 5,
                'firstName' => 'Elena',
                'paternalSurname' => 'Directora',
                'maternalSurname' => 'Depend5',
                'password' => Hash::make('password123'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'payroll' => 'user006',
                'role' => 'usuario',
                'dependence_id' => 6,
                'firstName' => 'Jorge',
                'paternalSurname' => 'Operador',
                'maternalSurname' => 'Depend6',
                'password' => Hash::make('password123'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);

        $permissionIds = DB::table('permissions')->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('user_permissions')->insert([
                'user_id' => 1,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }
        foreach ($departaments as $dep) {
            DB::table('dependences')->insert([
                'name' => $dep['name'],
                'color' => $dep['color'],
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('processes')->insert([
            [
                'model_class' => 'App\\Models\\Penalty',
                'orden'       => 1,
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'model_class' => 'App\\Models\\Publicsecurities',
                'orden'       => 2,
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'model_class' => 'App\\Models\\Traffic',
                'orden'       => 3,
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [
                'model_class' => 'App\\Models\\Court',
                'orden'       => 999, // siempre el último
                'active'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);
        DB::table('alcohol_range_rules')->insert([
            [
                'min_value'  => 0,
                'max_value'  => 1.99,
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'min_value'  => 2.00,
                'max_value'  => 2.99,
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'min_value'  => 3.00,
                'max_value'  => 90.00, // infinito
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('alcohol_range_rules_process')->insert([
            [
                'rule_id'    => 1,
                'process_id' => 1, // Penalty
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Rango 2: = 2 → Penalty + PublicSecurities
        DB::table('alcohol_range_rules_process')->insert([
            [
                'rule_id'    => 2,
                'process_id' => 1, // Penalty
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_id'    => 2,
                'process_id' => 2, // Publicsecurity
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        // Rango 3: >= 3 → Penalty + Publicsecurity + Traffic + Court
        DB::table('alcohol_range_rules_process')->insert([
            [
                'rule_id'    => 3,
                'process_id' => 1, // Penalty
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_id'    => 3,
                'process_id' => 2, // Publicsecurity
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_id'    => 3,
                'process_id' => 3, // Traffic
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'rule_id'    => 3,
                'process_id' => 4, // Court (final)
                'active'     => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
}
}


// const CustomHistoryPenalty = ({ id, open, setOpen }: { id: AlcoholCase["id"]; open: boolean; setOpen: () => void }) => {
//    const [history, setHistory] = useState({
//       data: [],
//       loading: false
//    });
//    const init = async () => {
//       setHistory((prev) => ({
//          ...prev,
//          loading: true
//       }));

//       try {
//          const res = await GetAxios(`penalties/historial/${id}`);

//          setHistory((prev) => ({
//             ...prev,
//             data: res?.data
//          }));
//       } catch (error) {
//          console.error(error);
//       } finally {
//          setHistory((prev) => ({
//             ...prev,
//             loading: false
//          }));
//       }
//    };

//    useEffect(() => {
//       init()
//    }, [id]);
//    useEffect(()=>{
//    },[history])
//    return (
//       <CustomModal  title={`Historial del detenido`} isOpen={open} onClose={() => setOpen()}>
//          <CustomTable
//             conditionExcel={"multas_exportar"}
//             loading={history.loading}
//             data={history.data as any}
//             paginate={[5, 10, 25, 50]}
//             columns={[
//                { field: "id", headerName: "Folio", visibility: "always" },
//                { field: "name", headerName: "Nombre del detenido", visibility: "always" },
//                { field: "detainee_released_to", headerName: "Persona que acudio", visibility: "always" },
//                {
//                   field: "image_penaltie",
//                   visibility: "expanded",
//                   headerName: "Foto Multa",
//                   renderField: (value) => <PhotoZoom src={value} alt={value} />
//                },
//                {
//                   field: "images_evidences",
//                   headerName: "Foto evidencia del ciudadano",
//                   visibility: "expanded",
//                   renderField: (value) => <PhotoZoom src={value} alt={value} />
//                },
//                { field: "doctor", headerName: "Doctor", visibility: "expanded" },
//                { field: "cedula", headerName: "Cedula del doctor", visibility: "expanded" },
//                {
//                   field: "time",
//                   headerName: "Hora",
//                   visibility: "always",
//                   renderField: (v) => <>{formatDatetime(`2025-01-01 ${v}`, true, DateFormat.H_MM_SS_A)}</>,
//                   getFilterValue: (v) => formatDatetime(`2025-01-01 ${v}`, true, DateFormat.H_MM_SS_A)
//                },
//                {
//                   field: "date",
//                   headerName: "Fecha",
//                   visibility: "always",
//                   renderField: (v) => <>{formatDatetime(v, true, DateFormat.DDDD_DD_DE_MMMM_DE_YYYY)}</>,
//                   getFilterValue: (v) => formatDatetime(v, true, DateFormat.DDDD_DD_DE_MMMM_DE_YYYY)
//                },
//                { field: "person_contraloria", headerName: "Contraloría", visibility: "expanded" },
//                { field: "oficial_payroll", headerName: "Nómina Oficial", visibility: "expanded" },
//                { field: "person_oficial", headerName: "Oficial", visibility: "expanded" },
//                { field: "vehicle_service_type", headerName: "Tipo de Servicio Vehicular", visibility: "expanded" },
//                { field: "alcohol_concentration", headerName: "Concentración Alcohol", visibility: "expanded" },
//                { field: "group", headerName: "Grupo", visibility: "expanded" },
//                { field: "municipal_police", headerName: "Policía Municipal", visibility: "expanded" },
//                { field: "civil_protection", headerName: "Protección Civil", visibility: "expanded" },
//                { field: "command_vehicle", headerName: "Vehículo Comando", visibility: "expanded" },
//                { field: "command_troops", headerName: "Tropa Comando", visibility: "expanded" },
//                { field: "command_details", headerName: "Detalles Comando", visibility: "expanded" },
//                { field: "filter_supervisor", headerName: "Supervisor Filtro", visibility: "expanded" },
//                { field: "cp", headerName: "Código Postal", visibility: "always" },
//                { field: "city", headerName: "Ciudad", visibility: "always" },
//                { field: "age", headerName: "Edad", visibility: "expanded" },
//                { field: "amountAlcohol", headerName: "Cantidad Alcohol", visibility: "expanded" },
//                { field: "number_of_passengers", headerName: "Número de Pasajeros", visibility: "expanded" },
//                { field: "plate_number", headerName: "Número de Placa", visibility: "expanded" },
//                { field: "detainee_phone_number", headerName: "Teléfono del Detenido", visibility: "expanded" },
//                { field: "curp", headerName: "CURP", visibility: "expanded" },
//                { field: "observations", headerName: "Observaciones", visibility: "expanded" },
//                { field: "created_by_name", headerName: "Creado Por", visibility: "expanded" },
//                {
//                   field: "active",
//                   headerName: "Activo",
//                   renderField: (v) => (
//                      <span className={`px-2 py-1 rounded-full text-xs font-semibold ${v ? "bg-green-100 text-green-800" : "bg-red-100 text-red-800"}`}>
//                         {v ? "Activo" : "Desactivado"}
//                      </span>
//                   )
//                }
//             ]}
//             // columns={tableColumns}
//          />
//       </CustomModal>
//    );
// };