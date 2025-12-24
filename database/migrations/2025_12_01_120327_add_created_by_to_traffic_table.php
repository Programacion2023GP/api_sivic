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
        Schema::table('traffic', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable(); // o después del campo que prefieras

            // $table->foreignId('created_by')->nullable()->constrained('users'); // o después del campo que prefieras
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('traffic', function (Blueprint $table) {
            $table->dropColumn('created_by');
        });
    }
};
