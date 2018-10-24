<?php namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;
use Auth;

class Controller extends \App\Http\Controllers\Controller
{
    protected $site = null;
    protected $user = null;

    public function __construct(Request $request)
    {
        // Load the site from $_GET
        if ($request->get('site_id'))
        {
            $user = Auth::guard('api')->user();
            if ($user)
            {
                $this->user = $user;
                $this->site = $user->sites()->find($request->get('site_id'));
            }
        }
    }

}
