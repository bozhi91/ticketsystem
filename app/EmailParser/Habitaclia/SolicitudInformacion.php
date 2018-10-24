<?php namespace App\EmailParser\Habitaclia;

class SolicitudInformacion extends \App\EmailParser\Base {

    public function parse(\App\PhpImap\IncomingMail $mail)
    {
        // Verify email from
        if ($mail->fromAddress != 'solicitudes@envios.habitaclia.com') {
            return false;
        }

        // Verify subject
        if (strpos($mail->getSubject(), 'tiene una solicitud de información - habitaclia') === false) {
            return false;
        }

        // Striped text
        $text = preg_replace('#\r|\n#', '', $mail->getContent());

        // Obtener email
        if (!preg_match('#mailto:([^"]+)#mis', $text, $match)) {
            return false;
        }
        $mail->fromAddress = trim($match[1]);

        // Obtener nombre
        if (preg_match('#<b>Nombre: </b></span>([^<]+)#mis', $text, $match)) {
            $mail->fromName = trim($match[1]);
        }

        // Obtener phone
        if (preg_match('#<b>Teléfono: </b></span>([^<]+)#mis', $text, $match)) {
            $mail->phone = trim($match[1]);
        }

        // Set referer
        $mail->referer = 'habitaclia';

        return $mail;
    }

}
