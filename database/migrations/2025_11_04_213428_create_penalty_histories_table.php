<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penalty_histories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penalty_id')->constrained('penalties')->onDelete('cascade'); // referencia al original
            $table->json('data'); // todos los campos guardados como JSON
            $table->foreignId('modified_by')->constrained('users'); // quien hizo la edición
            $table->string('action'); // ejemplo: created, updated, deleted
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penalty_histories');
    }
};
