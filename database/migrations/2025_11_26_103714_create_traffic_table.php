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
        Schema::create('traffic', function (Blueprint $table) {
            $table->id();
            $table->string('citizen_name')->nullable();
            $table->integer('age')->nullable(); // Se usa string porque tú aceptas number|string en TS
            $table->string('rank')->nullable();
            $table->string('plate_number')->nullable();
            $table->string('vehicle_brand')->nullable();
            $table->string('time')->nullable(); // String porque en TS lo usas como string
            $table->string('location')->nullable();
            $table->string('person_oficial')->nullable();
            $table->string("image_traffic")->nullable();
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('traffic');
    }
};
