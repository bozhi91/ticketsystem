<?php namespace App\EmailParser;

abstract class Base {

    public abstract function parse(\App\PhpImap\IncomingMail $mail);

}
