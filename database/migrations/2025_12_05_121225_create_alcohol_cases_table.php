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
        Schema::create('alcohol_cases', function (Blueprint $table) {
            $table->id();
            $table->decimal('alcohol_level', 4, 2); 
            $table->unsignedBigInteger('current_process_id')->nullable(); // proceso actual
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->boolean('requires_confirmation')->default(false);

            $table->foreign('current_process_id')
                ->references('id')->on('processes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alcohol_cases');
    }
};
