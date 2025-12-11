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
