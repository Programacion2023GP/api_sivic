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
        Schema::table('penalties', function (Blueprint $table) {
      $table->string('detention_reason')->nullable(); // "App\Models\Recepcion"
      $table->string('patrol_unit_number')->nullable(); // "App\Models\Recepcion"
      $table->string('fine_amount')->nullable(); // "App\Models\Recepcion"
      $table->string('exit_reason')->nullable(); // "App\Models\Recepcion"


            //
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penalties', function (Blueprint $table) {
            //
            $table->dropColumn('detention_reason');
            $table->dropColumn('patrol_unit_number');
            $table->dropColumn('fine_amount');
            $table->dropColumn('exit_reason');

        });
    }
};
