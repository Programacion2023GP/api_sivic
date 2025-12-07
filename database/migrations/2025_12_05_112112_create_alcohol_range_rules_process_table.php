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
        Schema::create('alcohol_range_rules_process', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('rule_id');
            $table->unsignedBigInteger('process_id');
            $table->boolean('active')->default(true);

            $table->foreign('rule_id')
                ->references('id')->on('alcohol_range_rules')
                ->onDelete('cascade');

            $table->foreign('process_id')
                ->references('id')->on('processes')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alcohol_range_rules_process');
    }
};
