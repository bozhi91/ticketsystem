<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class EmailParser extends Model
{
    public static $parsers = [
        '\App\EmailParser\Idealista\SolicitudVenta',
        '\App\EmailParser\Idealista\SolicitaInformacion',
        '\App\EmailParser\Pisos\AlguienInteresado',
        '\App\EmailParser\Habitaclia\SolicitudInformacion',
        '\App\EmailParser\Fotocasa\ApartamentoEnVenta',
    ];

    public static function parse(\App\PhpImap\IncomingMail $mail)
    {
        foreach (self::$parsers as $parser) {
            $adm = new $parser;
            $parsed = $adm->parse($mail);

            if ($parsed) {
                return $parsed;
            }
        }
    }

}
