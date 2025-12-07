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
        Schema::create('alcohol_range_rules', function (Blueprint $table) {
            $table->id();
            $table->decimal('min_value', 4, 2);
            $table->decimal('max_value', 4, 2)->nullable(); // null = infinito
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alcohol_range_rules');
    }
};
