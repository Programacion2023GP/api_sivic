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
        // 🔹 Truncar tabla de permisos
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 🔹 Permisos en español
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

        ];

        // 🔹 Insertar permisos
        foreach ($permissions as $permission) {
            DB::table('permissions')->insert([
                'name' => $permission,
                'active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->command->info('Permisos creados en español y tabla reiniciada.');

        // 🔹 Crear usuario nómina 000000
        DB::table('users')->updateOrInsert(
            ['payroll' => '000000'], // si existe, actualiza
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

        $userId = DB::table('users')->where('payroll', '000000')->value('id');

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
    }
}
