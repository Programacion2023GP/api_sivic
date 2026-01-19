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
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->foreignId('referring_agency')
                ->constrained('senders')
                ->onDelete('cascade');
            $table->string('detainee_name'); // nombre del detenido
            $table->text('exit_reason'); 
            $table->time('entry_time'); // hora de entrada
            $table->dateTime('exit_datetime')->nullable(); // hora y fecha de salida
            $table->foreignId('detention_reason') // motivo de detención
                ->constrained('causeOfDetention')
                ->onDelete('cascade');
            $table->decimal('fine_amount', 10, 2)->nullable(); // multa
            $table->boolean('active')->default(true);

            // relación con usuarios (quien lo creó)
            $table->foreignId('created_by')
                ->constrained('users')
                ->onDelete('cascade');
        
            $table->timestamps();
            $table->string("image_court")->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courts');
    }
};
