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
            $table->string('image_penaltie');
            $table->string('images_evidences');

            $table->string('person_contraloria');
            $table->string('oficial_payroll')->nullable();
            $table->string('person_oficial');
            $table->string('vehicle_service_type');
            $table->integer('alcohol_concentration');
            $table->integer('group');

            $table->string('municipal_police');
            $table->string('civil_protection');

            $table->string('command_vehicle');
            $table->string('command_troops');
            $table->string('command_details');
            $table->string('filter_supervisor');

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
