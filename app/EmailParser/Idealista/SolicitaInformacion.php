<?php namespace App\EmailParser\Idealista;

class SolicitaInformacion extends \App\EmailParser\Base {

    public function parse(\App\PhpImap\IncomingMail $mail)
    {
        // Verify subject
        if ($mail->getSubject() != 'idealista solicita información') {
            return false;
        }

        // Obtener email y nombre
        if (!preg_match('#Dirección de correo\:\s(.+?)\s#', $mail->textPlain, $match)) {
            return false;
        }
        $mail->fromName = trim($match[1]);
        $mail->fromAddress = trim($match[1]);

        // Obtener phone
        if (preg_match('#Teléfono\:\s(\+\d+\s\d+)#', $mail->textPlain, $match)) {
            $mail->phone = trim($match[1]);
        }

        // Set referer
        $mail->referer = 'idealista';

        return $mail;
    }

}
