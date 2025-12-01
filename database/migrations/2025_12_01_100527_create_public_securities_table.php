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
        Schema::create('public_securities', function (Blueprint $table) {
            $table->id();
            $table->string('detainee_name')->nullable();
            $table->string('officer_name')->nullable();
            $table->string('patrol_unit_number')->nullable();
            $table->string('detention_reason')->nullable();
            $table->date('date')->nullable();
            $table->time('time')->nullable();
            $table->integer('age')->nullable();
            $table->string('location')->nullable();
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->constrained('users'); // usuario que creó el registro

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('public_securities');
    }
};
