<?php namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;

use App\Http\Requests;
use ApiResponse;
use Validator;
use Auth;

class SiteController extends Controller
{
    public function index(Request $request)
    {
        $items = Auth::guard('api')->user()->sites()->get();
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

        $validator = \App\Site::getValidator($data);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        // Validate user data
        $user_data = $request->get('user');
        if (empty($user_data))
        {
            return ApiResponse::badRequest('The user field is required.');
        }

        $user_data['type'] = 'manager';

        // Exists a user with this email?
        $user = \App\User::whereEmail(@$user_data['email'])->enabled()->first();
        $user_exists = true;
        if (!$user)
        {
            $user_exists = false;

            $validator = \App\User::getValidator($user_data, ['site_id']);
            if ($validator->fails())
            {
                return ApiResponse::badRequest($validator->errors()->first());
            }

            // Create the user
            $user = new \App\User;
            $user->name = $user_data['name'];
            $user->email = $user_data['email'];
            $user->enabled = 1;
            $user->save();
        }
        else
        {
            // If the user exists, we need to match the token
            $auth = Auth::guard('api')->user();
            if (!$auth || $auth->id != $user->id)
            {
                return ApiResponse::badRequest('The email is already taken. If you want to create a new site for this email, please provide token in the request.');
            }
        }

        try
        {
            $site = \App\Site::saveModel($data);
        }
        catch (\Illuminate\Validation\ValidationException $e)
        {
            return ApiResponse::badRequest($e->validator->errors()->first());
        }
        catch (\Exception $e)
        {
            // Log exception on laravel log
            \Log::error($e);

            return ApiResponse::internalServerError();
        }

        // Attach the user to the site
        $user->sites()->attach($site->id, ['type' => $user_data['type']]);

        $response = ['id' => $site->id];
        if (!$user_exists)
        {
            $response['token'] = $user->api_token;
            $response['user_id'] = $user->id;
        }

        return ApiResponse::created($response, route('api.v1.site.show', $site->id));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = Auth::guard('api')->user();

        $item = $user->sites()->find($id);
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
        $site = Auth::guard('api')->user()->sites()->find($id);
        if (!$site)
        {
            return ApiResponse::notFound();
        }

        $data = $request->all();

        $validator = \App\Site::getUpdateValidator($data, $id);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        try
        {
            $item = \App\Site::saveModel($data, $id);
        }
        catch (\Illuminate\Validation\ValidationException $e)
        {
            return ApiResponse::badRequest($e->validator->errors()->first());
        }
        catch (\Exception $e)
        {
            // Log exception on laravel log
            \Log::error($e);

            return ApiResponse::internalServerError();
        }

        return ApiResponse::updated(route('api.v1.site.show', $site->id));
    }

    public function store_user(Request $request, $id)
    {
        $site = Auth::guard('api')->user()->sites()->find($id);
        if (!$site)
        {
            return ApiResponse::notFound();
        }

        $data = $request->all();

        $validator = \Validator::make($data, [
            'users' => 'required|array'
        ]);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        // Map items
        $users = [];
        foreach ($data['users'] as $u)
        {
            $validator = \Validator::make($u, [
                'id' => 'required|integer',
                'type' => 'required|in:agent,manager'
            ]);
            if ($validator->fails())
            {
                return ApiResponse::badRequest($validator->errors()->first());
            }

            $users[$u['id']] = $u;
        }
        $users_id = array_keys($users);

        // Validate users
        $items = \App\User::whereIn('id', $users_id)->get()->lists('id')->toArray();
        $items_diff = array_values(array_diff($users_id, $items));
        if (!empty($items_diff))
        {
            return ApiResponse::badRequest('Users not found: '.implode(', ', $items_diff).'.');
        }

        // Remove items already related
        $current = $site->users()->lists('users.id')->toArray();
        $users_id = array_diff($users_id, $current);

        try
        {
            // Create the new ones
            foreach ($users as $user_id => $u)
            {
                if (in_array($user_id, $current))
                {
                    $site->users()->updateExistingPivot($user_id, ['type' => $users[$user_id]['type']]);
                }
                else
                {
                    $site->users()->attach($user_id, ['type' => $users[$user_id]['type']]);
                }
            }
        }
        catch (\Exception $e)
        {
            return ApiResponse::badRequest($e->getMessage());
        }

        return ApiResponse::updated(route('api.v1.site.show', $site->id));
    }

    public function delete_user(Request $request, $id)
    {
        $site = Auth::guard('api')->user()->sites()->find($id);
        if (!$site)
        {
            return ApiResponse::notFound();
        }

        $data = $request->all();

        $validator = \Validator::make($data, [
            'users' => 'required|array'
        ]);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        $users_id = $data['users'];

        // Validate users
        $items = \App\User::whereIn('id', $users_id)->get()->lists('id')->toArray();
        $items_diff = array_values(array_diff($users_id, $items));
        if (!empty($items_diff))
        {
            return ApiResponse::badRequest('Users not found: '.implode(', ', $items_diff).'.');
        }

        try
        {
            $site->users()->detach($users_id);
        }
        catch (\Exception $e)
        {
            return ApiResponse::badRequest($e->getMessage());
        }

        return ApiResponse::updated(route('api.v1.site.show', $site->id));
    }

}
