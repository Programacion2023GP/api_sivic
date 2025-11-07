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
            $table->string('time');
            $table->date('date');
            $table->string('image_penaltie')->nullable();
            $table->string('images_evidences')->nullable();

            $table->string('person_contraloria');
            $table->string('oficial_payroll')->nullable();
            $table->string('person_oficial')->nullable();
            $table->string('vehicle_service_type')->nullable();
            $table->integer('alcohol_concentration')->nullable();
            $table->integer('group')->nullable();

            $table->string('municipal_police')->nullable();
            $table->string('civil_protection')->nullable();

            $table->string('command_vehicle')->nullable();
            $table->string('command_troops')->nullable();
            $table->string('command_details')->nullable();
            $table->string('filter_supervisor')->nullable();
            $table->string('detainee_released_to')->nullable();

            $table->string('name');
            $table->string('cp');
            $table->string('city');
            $table->integer('age');
            $table->float('amountAlcohol');
            $table->integer('number_of_passengers')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('detainee_phone_number')->nullable();
            $table->string('curp');
            $table->text('observations')->nullable();
            $table->boolean('active')->default(true);

            $table->foreignId('created_by')->constrained('users'); // usuario que creó el registro
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalties');
    }
};
