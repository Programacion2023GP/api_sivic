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
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        DB::table('permissions')->truncate();
        DB::table('user_permissions')->truncate();
        DB::table('users')->truncate();
        DB::table('dependences')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $permissions = [
            'usuarios_crear',
            'usuarios_actualizar',
            'usuarios_eliminar',
            'usuarios_ver',
            'usuarios_exportar',
            'vista_logs',
            'multas_crear',
            'multas_actualizar',
            'multas_eliminar',
            'multas_ver',
            'multas_exportar',
            'multas_historial',
            'catalogo_dependencia_ver',
            'catalogo_dependencia_crear',
            'catalogo_dependencia_actualizar',
            'catalogo_dependencia_eliminar',
            'catalogo_dependencia_exportar',
            'juzgados_ver',
            'juzgados_crear',
            'juzgados_actualizar',
            'juzgados_eliminar',
            'transito_vialidad__ver',
            'transito_vialidad__crear',
            'transito_vialidad__actualizar',
            'transito_vialidad__eliminar',
            'seguridad_publica_ver',
            'seguridad_publica__crear',
            'seguridad_publica__actualizar',
            'seguridad_publica__eliminar',

            
            'vista_reports',
            'reports_dashboard'
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
            ['name' => 'Secretaría del Ayuntamiento', 'color' => '#1E90FF'], // Azul institucional
            ['name' => 'Juzgados Cívico Municipales', 'color' => '#6A5ACD'], // Azul violeta, sobrio
            ['name' => 'Contraloría Municipal', 'color' => '#2E8B57'], // Verde formal
            ['name' => 'Dirección de Tecnologías de la Información', 'color' => '#00CED1'], // Cian tecnológico
            ['name' => 'Tránsito y Vialidad', 'color' => '#FFD700'], // Amarillo tráfico
            ['name' => 'Dirección de Seguridad Pública', 'color' => '#B22222'], // Rojo institucional / seguridad
        ];

        // 🔹 Insertar permisos
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

        // 🔹 Crear usuario nómina 000000
        DB::table('users')->updateOrInsert(
            ['payroll' => 'admin'], // si existe, actualiza
            [
                'firstName' => 'Admin',
                'paternalSurname' => 'Desarrollo',
                'maternalSurname' => '',
                'password' => Hash::make('desarrollo'),
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $userId = DB::table('users')->where('payroll', 'admin')->value('id');

        // 🔹 Asignar todos los permisos a este usuario
        $permissionIds = DB::table('permissions')->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('user_permissions')->updateOrInsert(
                [
                    'user_id' => $userId,
                    'permission_id' => $permissionId,
                ],
                ['created_at' => now(), 'updated_at' => now()]
            );
        }

        $this->command->info('Todos los permisos asignados al usuario nómina 000000.');
    


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
                'max_value'  => null, // infinito
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
