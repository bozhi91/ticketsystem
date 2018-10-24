<?php namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;

use App\Http\Requests;
use ApiResponse;
use Validator;
use Auth;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $items = $this->site->contacts()->get();
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

        $validator = \App\Contact::getValidator($data);
        if ($validator->fails())
        {
            $messages = $validator->errors();
            $undeleted = \App\Contact::where('site_id', $data['site_id'])->where('email', $data['email'])->whereNull('deleted_at')->count();
            if ($undeleted || $messages->count()>1 || !$messages->has('email')) {
                return ApiResponse::badRequest($validator->errors()->first());
            }
        }

        $item = \App\Contact::saveModel($data);
        if (!$item)
        {
            return ApiResponse::internalServerError();
        }

        return ApiResponse::created(['id' => $item->id], route('api.v1.contact.show', $item->id));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = $this->site->contacts()->find($id);
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
        $item = \App\Contact::find($id);
        if (!$item)
        {
            return ApiResponse::notFound();
        }

        $data = $request->all();
        $data['site_id'] = $item->site_id;

        $validator = \App\Contact::getUpdateValidator($data, $id);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        $item = \App\Contact::saveModel($data, $id);
        if (!$item)
        {
            return ApiResponse::internalServerError();
        }

        // Attach items?
        if (isset($data['items']) && is_array($data['items']))
        {
            // Validate items
            $items = $this->site->items()->whereIn(\DB::raw('items.id'), $data['items'])->get()->lists('id')->toArray();
            $items_diff = array_values(array_diff($data['items'], $items));
            if (!empty($items_diff))
            {
                return ApiResponse::badRequest('Items not found: '.implode(', ', $items_diff).'.');
            }

            try
            {
                $item->items()->sync($data['items']);
            }
            catch (\Exception $e)
            {
                return ApiResponse::badRequest($e->getMessage());
            }
        }

        return ApiResponse::updated(route('api.v1.contact.show', $item->id));
    }

    /**
     * Deletes the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = $this->site->contacts()->find($id);
        if (!$item)
        {
            return ApiResponse::notFound();
        }

        $item->delete();

        return ApiResponse::deleted();
    }

    public function store_item(Request $request, \App\Contact $contact)
    {
        $data = $request->all();

        $validator = \Validator::make($data, [
            'items' => 'required|array'
        ]);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        // Validate items
        $items = $this->site->items()->whereIn(\DB::raw('items.id'), $data['items'])->get()->lists('id')->toArray();
        $items_diff = array_values(array_diff($data['items'], $items));
        if (!empty($items_diff))
        {
            return ApiResponse::badRequest('Items not found: '.implode(', ', $items_diff).'.');
        }

        // Remove items already related
        $current = $contact->items()->lists('items.id')->toArray();
        $data['items'] = array_diff($data['items'], $current);

        try
        {
            $contact->items()->attach($data['items']);
        }
        catch (\Exception $e)
        {
            return ApiResponse::badRequest($e->getMessage());
        }

        return ApiResponse::updated(route('api.v1.contact.show', $contact->id));
    }

    public function delete_item(Request $request, \App\Contact $contact)
    {
        $data = $request->all();

        $validator = \Validator::make($data, [
            'items' => 'required|array'
        ]);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        // Validate items
        $items = $this->site->items()->whereIn(\DB::raw('items.id'), $data['items'])->get()->lists('id')->toArray();
        $items_diff = array_values(array_diff($data['items'], $items));
        if (!empty($items_diff))
        {
            return ApiResponse::badRequest('Items not found: '.implode(', ', $items_diff).'.');
        }

        try
        {
            $contact->items()->detach($data['items']);
        }
        catch (\Exception $e)
        {
            return ApiResponse::badRequest($e->getMessage());
        }

        return ApiResponse::updated(route('api.v1.contact.show', $contact->id));
    }
}
