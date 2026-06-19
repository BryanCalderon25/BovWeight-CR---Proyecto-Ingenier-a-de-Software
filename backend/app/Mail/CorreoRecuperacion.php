<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorreoRecuperacion extends Mailable
{
    use Queueable, SerializesModels;

    public $codigo;

    /**
     * Crear una nueva instancia de mensaje.
     */
    public function __construct($codigo)
    {
        $this->codigo = $codigo;
    }

    /**
     * Construir el mensaje.
     */
    public function build()
    {
        return $this->subject('Recuperación de contraseña - BovWeight CR')
                    ->html("Recibimos una solicitud para restablecer su contraseña en BovWeight CR.<br><br>Su código de recuperación es: <b>{$this->codigo}</b><br><br>Este código expirará en 10 minutos.<br><br>Si usted no solicitó este cambio, ignore este mensaje.");
    }
}
