<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("
     CREATE OR REPLACE VIEW  penalties_latest_view AS
        SELECT 
            p.*,
            u.fullName as created_by_name,
            u.dependence_id as user_dependence_id,
            u.role as user_role,
            (SELECT COUNT(*) 
             FROM penalties p2 
             INNER JOIN users u2 ON p2.created_by = u2.id
             WHERE p2.curp = p.curp 
             AND p2.active = 1 
             AND u2.dependence_id = u.dependence_id) > 1 as has_history
        FROM penalties p
        INNER JOIN users u ON p.created_by = u.id
        WHERE p.active = 1
        AND p.id IN (
            SELECT MAX(p3.id)
            FROM penalties p3
            INNER JOIN users u3 ON p3.created_by = u3.id
            WHERE p3.active = 1
            GROUP BY p3.curp, u3.dependence_id
        )
    ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalties_latest_view');
    }
};
