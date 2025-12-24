<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alcohol_cases', function (Blueprint $table) {
            $table->id();
            $table->decimal('alcohol_level', 4, 2);
            $table->unsignedBigInteger('current_process_id')->nullable();

            $table->string('time');
            $table->date('date');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->boolean('requires_confirmation')->default(false);

            $table->string('name')->nullable();
            $table->string('city')->nullable();
            $table->integer('cp')->nullable();

            $table->string('plate_number')->nullable();
            $table->integer('age')->nullable();
            $table->double('long')->nullable();
            $table->double('lat')->nullable();
            $table->boolean('finish')->default(false);

            // residencia
            $table->unsignedBigInteger('residence_folio')->nullable()->index();

            // FK proceso
            $table->foreign('current_process_id')
                ->references('id')->on('processes');
        });

        Schema::table('alcohol_cases', function (Blueprint $table) {
            $table->foreign('residence_folio', 'fk_alcohol_cases_residence')
                ->references('id')->on('alcohol_cases')
                ->onDelete('set null')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('alcohol_cases', function (Blueprint $table) {
            $table->dropForeign('fk_alcohol_cases_residence');
        });

        Schema::dropIfExists('alcohol_cases');
    }
};
