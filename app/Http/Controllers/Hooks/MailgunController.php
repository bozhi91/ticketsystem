<?php namespace App\Http\Controllers\Hooks;

use Illuminate\Http\Request;

use App\Http\Requests;

class MailgunController extends \App\Http\Controllers\Controller
{
    public function stored($domain, Request $request)
    {
        dd($domain, $request->all());
    }

}
