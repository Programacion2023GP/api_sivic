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
        Schema::create('penalty_preload_data', function (Blueprint $table) {
            $table->id();
            $table->integer('oficial_payroll')->nullable();
            $table->string('person_oficial', 255)->nullable();
            $table->string('civil_protection', 255)->nullable();
            $table->string('command_vehicle', 255)->nullable();
            $table->string('command_troops', 255)->nullable();
            $table->integer('group')->nullable();
            $table->string('person_contraloria', 255)->nullable();
            $table->string('command_details', 255)->nullable();
            $table->string('filter_supervisor', 255)->nullable();
            $table->unsignedBigInteger('doctor_id')->nullable();
            $table->dateTime('init_date')->nullable();
            $table->dateTime('final_date')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->boolean('active')->default(true);
            $table->timestamps();

            // Foreign keys
            $table->foreign('doctor_id')
                ->references('id')
                ->on('doctor')
                ->onDelete('set null');

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penalty_preload_data');
    }
};
