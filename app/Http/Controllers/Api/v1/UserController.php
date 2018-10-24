<?php namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;

use App\Http\Requests;
use ApiResponse;
use Validator;
use Auth;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->site->users()->enabled()->get();
        foreach ($items as $i => $item) {
            $items[$i]['email_accounts'] = $item->email_accounts($this->site->id)->get();
        }
        return ApiResponse::success($items);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $data['site_id'] = $this->site->id;

        $validator = \App\User::getValidator($data);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        // Create the user
        $item = new \App\User;
        $item->name = $data['name'];
        $item->email = $data['email'];
        $item->enabled = true;
        $item->save();

        // Attach to the site
        $item->sites()->attach($this->site->id, ['type' => $data['type']]);

        return ApiResponse::created(['id' => $item->id, 'token' => $item->api_token], route('api.v1.user.show', $item->id));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = $this->site->users()->enabled()->find($id);
        if (!$item)
        {
            return ApiResponse::notFound();
        }

        return ApiResponse::success($item);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $item = $this->site->users()->enabled()->find($id);
        if (!$item)
        {
            return ApiResponse::notFound();
        }

        $data = $request->all();

        $validator = \App\User::getUpdateValidator($data, $id);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        // For now, disable change user type
        if (!empty($data['type']))
        {
            unset($data['type']);
        }

        foreach (\App\User::getUpdateValidatorFields($id) as $key => $value)
        {
            if (!empty($data[$key]))
            {
                $item->$key = $data[$key];
            }
        }
        $item->save();

        return ApiResponse::updated(route('api.v1.user.show', $item->id));
    }

    public function reasign(Request $request, $id)
    {
        $origin = $this->site->users()->enabled()->find($id);
        if (!$origin)
        {
            return ApiResponse::notFound();
        }

        $data = $request->all();

        $validator = \Validator::make($data, [
            'user_id' => 'required'
        ]);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        $destination = $this->site->users()->enabled()->find($data['user_id']);
        if (!$destination)
        {
            return ApiResponse::notFound('Destination user not found.');
        }

        // Reasign
        $origin->reasign($destination, $this->site->id);

        return ApiResponse::updated(route('api.v1.user.show', $origin->id));
    }

    public function contacts(Request $request, $id)
    {
        $user = $this->site->users()->where(\DB::raw('users.id'), $id)->first();
        if (!$user)
        {
            return ApiResponse::notFound('User not found.');
        }

        $contacts = $user->contacts;

        return $contacts;
    }

}
