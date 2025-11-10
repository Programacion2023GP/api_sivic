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
        // 🔹 Truncar tablas
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('permissions')->truncate();
        DB::table('dependences')->truncate();
        DB::table('users')->truncate();
        DB::table('penalties')->truncate();
        DB::table('user_permissions')->truncate();
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
            'multas_historial',
            'catalogo_dependencia_ver',
            'catalogo_dependencia_crear',
            'catalogo_dependencia_actualizar',
            'catalogo_dependencia_eliminar',
            'catalogo_dependencia_exportar',
            'reports_dashboard'
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

        // 🔹 Crear usuario nómina 000000
        DB::table('users')->insert([
            'id' => 1,
            'payroll' => '000000',
                  'role'=> "sistemas",

            'firstName' => 'Admin',
            'paternalSurname' => 'Desarrollo',
            'maternalSurname' => '',
            'password' => Hash::make('desarrollo'),
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 🔹 Asignar todos los permisos al usuario 1
        $permissionIds = DB::table('permissions')->pluck('id');
        foreach ($permissionIds as $permissionId) {
            DB::table('user_permissions')->insert([
                'user_id' => 1,
                'permission_id' => $permissionId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // $faker = \Faker\Factory::create('es_ES');

        // // 🔥 GENERAR 300 PENALTIES (MULTAS)
        // $vehicleTypes = ['Particular', 'Publico', 'Comercial', 'Oficial', 'Servicio'];
        // $cities = ['Ciudad de Mexico', 'Guadalajara', 'Monterrey', 'Puebla', 'Leon', 'Tijuana', 'Cancun'];
        // $municipalPolice = ['Patrulla 101', 'Patrulla 102', 'Patrulla 103', 'Patrulla 201', 'Patrulla 202'];
        // $civilProtection = ['Unidad CP-1', 'Unidad CP-2', 'Unidad CP-3', 'Unidad CP-4'];
        // $commandVehicles = ['Vehiculo Mando 1', 'Vehiculo Mando 2', 'Vehiculo Mando 3'];
        // $detaineeReleasedTo = ['Familiar', 'Abogado', 'Autoridad competente', 'Liberado'];

        // for ($i = 1; $i <= 300; $i++) {
        //     $firstName = $this->removeAccents($faker->firstName);
        //     $paternalSurname = $this->removeAccents($faker->lastName);
        //     $maternalSurname = $this->removeAccents($faker->lastName);
        //     $fullName = $firstName . ' ' . $paternalSurname . ' ' . $maternalSurname;

        //     DB::table('penalties')->insert([
        //         'time' => $faker->time('H:i'),
        //         'date' => $faker->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        //         'image_penaltie' => $faker->optional(0.3)->imageUrl(400, 300, 'traffic'),
        //         'images_evidences' => $faker->optional(0.5)->imageUrl(400, 300, 'evidence'),

        //         'person_contraloria' => $this->removeAccents($faker->name),
        //         'oficial_payroll' => str_pad($faker->numberBetween(1000, 9999), 6, '0', STR_PAD_LEFT),
        //         'person_oficial' => $this->removeAccents($faker->name),
        //         'vehicle_service_type' => $faker->randomElement($vehicleTypes),
        //         'alcohol_concentration' => $faker->optional(0.8)->numberBetween(10, 400),
        //         'group' => $faker->optional(0.6)->numberBetween(1, 5),

        //         'municipal_police' => $faker->optional(0.7)->randomElement($municipalPolice),
        //         'civil_protection' => $faker->optional(0.4)->randomElement($civilProtection),

        //         'command_vehicle' => $faker->optional(0.3)->randomElement($commandVehicles),
        //         'command_troops' => $faker->optional(0.2)->numberBetween(2, 8),
        //         'command_details' => $faker->optional(0.3)->sentence(6),
        //         'filter_supervisor' => $this->removeAccents($faker->optional(0.5)->name),
        //         'detainee_released_to' => $faker->optional(0.6)->randomElement($detaineeReleasedTo),

        //         'name' => $fullName,
        //         'cp' => $faker->postcode,
        //         'city' => $faker->randomElement($cities),
        //         'age' => $faker->numberBetween(18, 70),
        //         'amountAlcohol' => $faker->randomFloat(2, 0.10, 1.50),
        //         'number_of_passengers' => $faker->optional(0.7)->numberBetween(0, 4),
        //         'plate_number' => strtoupper($faker->bothify('???-####')),
        //         'detainee_phone_number' => $faker->optional(0.8)->phoneNumber,
        //         'curp' => $this->generateSimpleCURP($firstName, $paternalSurname, $maternalSurname, $faker),
        //         'observations' => $faker->optional(0.6)->text(200),
        //         'active' => $faker->boolean(90),

        //         'created_by' => 1,
        //         'created_at' => $faker->dateTimeBetween('-1 year', 'now'),
        //         'updated_at' => now(),
        //     ]);

        //     if ($i % 50 === 0) {
        //         $this->command->info("$i penalties creados...");
        //     }
        // }

        $this->command->info('300 penalties creados exitosamente por el usuario 1.');
    }

    /**
     * Remover acentos y caracteres especiales
     */
    // private function removeAccents($string)
    // {
    //     $replace = [
    //         'Á' => 'A',
    //         'É' => 'E',
    //         'Í' => 'I',
    //         'Ó' => 'O',
    //         'Ú' => 'U',
    //         'á' => 'a',
    //         'é' => 'e',
    //         'í' => 'i',
    //         'ó' => 'o',
    //         'ú' => 'u',
    //         'Ñ' => 'N',
    //         'ñ' => 'n',
    //         'Ü' => 'U',
    //         'ü' => 'u'
    //     ];

    //     return strtr($string, $replace);
    // }

    /**
     * Generar CURP simple sin caracteres especiales
     */
    // private function generateSimpleCURP($firstName, $paternalSurname, $maternalSurname, $faker)
    // {
    //     // Primera letra del apellido paterno
    //     $firstLetterPaternal = substr($paternalSurname, 0, 1);

    //     // Primera vocal interna del apellido paterno
    //     $firstVowelPaternal = $this->getFirstVowel($paternalSurname);

    //     // Primera letra del apellido materno
    //     $firstLetterMaternal = substr($maternalSurname, 0, 1);

    //     // Primera letra del nombre
    //     $firstLetterName = substr($firstName, 0, 1);

    //     // Fecha de nacimiento (formato: AAMMDD)
    //     $birthDate = $faker->dateTimeBetween('-70 years', '-18 years');
    //     $year = $birthDate->format('y');
    //     $month = $birthDate->format('m');
    //     $day = $birthDate->format('d');

    //     // Sexo (H o M)
    //     $gender = $faker->randomElement(['H', 'M']);

    //     // Estado (código de 2 letras)
    //     $stateCode = $faker->randomElement(['AS', 'BC', 'BS', 'CC', 'CS', 'CH', 'CL', 'CM', 'DF', 'DG', 'GT', 'GR', 'HG', 'JC', 'MC', 'MN', 'MS', 'NT', 'NL', 'OC', 'PL', 'QT', 'QR', 'SP', 'SL', 'SR', 'TC', 'TS', 'TL', 'VZ', 'YN', 'ZS']);

    //     // Primera consonante interna del apellido paterno
    //     $consonantPaternal = $this->getFirstInternalConsonant($paternalSurname);

    //     // Primera consonante interna del apellido materno
    //     $consonantMaternal = $this->getFirstInternalConsonant($maternalSurname);

    //     // Primera consonante interna del nombre
    //     $consonantName = $this->getFirstInternalConsonant($firstName);

    //     // Dígito para evitar repetición y homoclave
    //     $digit = $faker->randomElement(['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z']);
    //     $homoclave = $faker->numberBetween(0, 9);

    //     return strtoupper(
    //         $firstLetterPaternal .
    //             $firstVowelPaternal .
    //             $firstLetterMaternal .
    //             $firstLetterName .
    //             $year . $month . $day .
    //             $gender .
    //             $stateCode .
    //             $consonantPaternal .
    //             $consonantMaternal .
    //             $consonantName .
    //             $digit .
    //             $homoclave
    //     );
    // }

    // private function getFirstVowel($string)
    // {
    //     $vowels = ['A', 'E', 'I', 'O', 'U'];
    //     $string = strtoupper($string);

    //     for ($i = 1; $i < strlen($string); $i++) {
    //         if (in_array($string[$i], $vowels)) {
    //             return $string[$i];
    //         }
    //     }
    //     return 'X';
    // }

    // private function getFirstInternalConsonant($string)
    // {
    //     $consonants = ['B', 'C', 'D', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'P', 'Q', 'R', 'S', 'T', 'V', 'W', 'X', 'Y', 'Z'];
    //     $string = strtoupper($string);

    //     for ($i = 1; $i < strlen($string); $i++) {
    //         if (in_array($string[$i], $consonants)) {
    //             return $string[$i];
    //         }
    //     }
    //     return 'X';
    // }
}
