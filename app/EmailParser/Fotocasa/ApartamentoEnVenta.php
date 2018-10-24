<?php namespace App\EmailParser\Fotocasa;

class ApartamentoEnVenta extends \App\EmailParser\Base {

    public function parse(\App\PhpImap\IncomingMail $mail)
    {
        // Verify email from
        if (strpos($mail->fromAddress, '@messaging.fotocasa.es') === false) {
            return false;
        }

        // Striped text
        $text = preg_replace('#\r|\n#', '', $mail->getContent());

        // Obtener email
        if (!preg_match('#<strong>E-mail:</strong>\s+([^<\s]+)#mis', $text, $match)) {
            return false;
        }
        $mail->fromAddress = trim($match[1]);


        // Obtener nombre
        if (preg_match('#A <strong>([^<]+)</strong> le interesa tu anuncio#mis', $text, $match)) {
            $mail->fromName = trim($match[1]);
        }

        // Obtener phone
        if (preg_match('#<strong>Mi tel&\#233;fono:</strong>\s+([^<\s]+)#mis', $text, $match)) {
            $mail->phone = trim($match[1]);
        }

        // Set referer
        $mail->referer = 'fotocasa';

        return $mail;
    }

}
