<?php namespace App\EmailParser\Idealista;

class SolicitudVenta extends \App\EmailParser\Base {

    public function parse(\App\PhpImap\IncomingMail $mail)
    {
        // Verify email from
        if ($mail->fromAddress != 'noreply.pro.es@idealista.com') {
            return false;
        }

        // Verify subject
        if (strpos($mail->getSubject(), 'Un usuario está interesado en tu inmueble') === false) {
            return false;
        }

        // Obtener email
        if (!preg_match('#Email\:\s(.+?)<#', $mail->getContent(), $match)) {
            return false;
        }
        $mail->fromAddress = trim($match[1]);

        // Obtener nombre
        if (!preg_match('#Nombre\:\s(.+?)<#', $mail->getContent(), $match)) {
           return false;
        }
        $mail->fromName = trim($match[1]);

        // Obtener phone
        if (preg_match('#Telefono\:\s([\d\s]+?)<#', $mail->getContent(), $match)) {
            $mail->phone = trim($match[1]);
        }

        // Set referer
        $mail->referer = 'idealista';

        return $mail;
    }

}
