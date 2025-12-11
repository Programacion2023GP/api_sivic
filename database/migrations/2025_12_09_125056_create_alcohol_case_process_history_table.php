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
        Schema::create('alcohol_case_process_history', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('case_id');      // alcohol_cases.id
            $table->unsignedBigInteger('process_id');   // processes.id
            $table->unsignedBigInteger('user_id')->nullable(); // quién completó el paso
            $table->unsignedBigInteger('step_id');   // processes.id

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alcohol_case_process_history');
    }
};
