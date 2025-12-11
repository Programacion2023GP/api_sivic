<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalties', function (Blueprint $table) {
            $table->id();

            $table->string('image_penaltie')->nullable();
            $table->string('images_evidences')->nullable();
            $table->string('images_evidences_car')->nullable();

          
            $table->string('vehicle_service_type')->nullable();
            $table->integer('alcohol_concentration')->nullable();

            $table->string('municipal_police')->nullable();

            $table->string('detainee_released_to')->nullable();

         
            $table->float('amountAlcohol');
            $table->integer('number_of_passengers')->nullable();
            $table->string('detainee_phone_number')->nullable();
            $table->string('curp')->nullable();
            $table->text('observations')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('penalty_preload_data_id')->nullable();

            $table->foreignId('created_by')->constrained('users'); // usuario que creó el registro
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalties');
    }
};