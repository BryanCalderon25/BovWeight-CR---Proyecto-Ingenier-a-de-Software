<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CorreoRecuperacion extends Mailable
{
    use Queueable, SerializesModels;

    public $enlace;

    /**
     * Crear una nueva instancia de mensaje.
     */
    public function __construct($enlace)
    {
        $this->enlace = $enlace;
    }

    /**
     * Construir el mensaje.
     */
    public function build()
    {
        return $this->subject('Restablecer contraseña - BovWeight CR')
                    ->html("Recibimos una solicitud para restablecer su contraseña en BovWeight CR. Presione el siguiente enlace para crear una nueva contraseña. Si usted no solicitó este cambio, puede ignorar este correo.<br><br><a href='{$this->enlace}'>{$this->enlace}</a>");
    }
}
