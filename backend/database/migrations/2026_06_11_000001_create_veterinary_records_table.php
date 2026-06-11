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
        Schema::create('veterinary_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('animal_id')->constrained('animals')->onDelete('cascade');
            $table->foreignId('veterinario_id')->nullable()->constrained('users')->onDelete('set null');

            $table->date('fecha_atencion');
            $table->enum('tipo', ['observacion', 'tratamiento', 'vacuna', 'cirugia', 'revision'])
                  ->default('observacion');

            $table->text('diagnostico')->nullable();
            $table->text('tratamiento')->nullable();
            $table->text('medicamentos')->nullable();
            $table->string('dosis')->nullable();
            $table->date('proxima_cita')->nullable();
            $table->text('observaciones')->nullable();
            $table->text('recomendaciones')->nullable();
            $table->decimal('peso_al_momento', 8, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('veterinary_records');
    }
};
