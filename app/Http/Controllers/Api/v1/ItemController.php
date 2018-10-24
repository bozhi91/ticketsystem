<?php namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;

use App\Http\Requests;
use ApiResponse;
use Validator;
use Auth;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->site->items()->with('contacts')->get();
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

        $validator = \App\Item::getValidator($data);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        // Check reference for the site
        $found = \App\Item::whereReference($data['reference'])->whereSiteId($data['site_id'])->first();
        if ($found)
        {
            return ApiResponse::badRequest('The reference has already been taken.');
        }

        $item = new \App\Item;
        $item->site_id = $data['site_id'];
        $item->reference = $data['reference'];
        $item->type = $data['type'];
        $item->title = $data['title'];
        $item->image = !empty($data['image']) ? $data['image'] : null;
        $item->url = !empty($data['url']) ? $data['url'] : null;
        $item->data = !empty($data['data']) ? $data['data'] : null;
        $item->save();

        return ApiResponse::created(['id' => $item->id], route('api.v1.item.show', $item->id));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = $this->site->items()->with('contacts')->find($id);
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
        $data = $request->all();
        $data['site_id'] = $this->site->id;

        $validator = \App\Item::getUpdateValidator($data, $id);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        $item = $this->site->items()->find($id);
        if (!$item)
        {
            return ApiResponse::notFound();
        }

        foreach (\App\Item::getUpdateValidatorFields($id) as $key => $value)
        {
            if (!empty($data[$key]))
            {
                $item->$key = $data[$key];
            }
        }

        // Attach users?
        if (!empty($data['users']) && is_array($data['users']))
        {
            // Validate users
            $users = $this->site->users()->whereIn(\DB::raw('users.id'), $data['users'])->enabled()->get()->lists('id')->toArray();
            $users_diff = array_values(array_diff($data['users'], $users));
            if (!empty($users_diff))
            {
                return ApiResponse::badRequest('Users not found: '.implode(', ', $users_diff).'.');
            }

            try
            {
                $item->users()->sync($data['users']);
            }
            catch (\Exception $e)
            {
                return ApiResponse::badRequest($e->getMessage());
            }
        }

        $item->save();

        return ApiResponse::updated(route('api.v1.item.show', $item->id));
    }

}
