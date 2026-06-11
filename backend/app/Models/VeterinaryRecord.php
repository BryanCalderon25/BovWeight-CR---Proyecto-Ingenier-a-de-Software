<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VeterinaryRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'animal_id',
        'veterinario_id',
        'fecha_atencion',
        'tipo',
        'diagnostico',
        'tratamiento',
        'medicamentos',
        'dosis',
        'proxima_cita',
        'observaciones',
        'recomendaciones',
        'peso_al_momento',
    ];

    protected $casts = [
        'fecha_atencion' => 'date',
        'proxima_cita'   => 'date',
    ];

    /**
     * Animal al que pertenece este registro veterinario.
     */
    public function animal()
    {
        return $this->belongsTo(Animal::class);
    }

    /**
     * Veterinario (usuario) que realizó la atención.
     */
    public function veterinario()
    {
        return $this->belongsTo(User::class, 'veterinario_id');
    }
}
