<?php namespace App\EmailParser\Pisos;

class AlguienInteresado extends \App\EmailParser\Base {

    public function parse(\App\PhpImap\IncomingMail $mail)
    {
        // Verify email from
        if ($mail->fromAddress != 'noreply@pisos.com') {
            return false;
        }

        // Verify subject
        if (strpos($mail->getSubject(), 'Alguien está interesado en tu inmueble') === false) {
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
        if (preg_match('#<b>Nombre</b>.+?">([^<]+)#mis', $text, $match)) {
            $mail->fromName = trim($match[1]);
        }

        // Obtener phone
        if (preg_match('#<b>Teléfono</b>.+?">([^<]+)#mis', $text, $match)) {
            $mail->phone = trim($match[1]);
        }

        // Set referer
        $mail->referer = 'pisos.com';

        return $mail;
    }

}
