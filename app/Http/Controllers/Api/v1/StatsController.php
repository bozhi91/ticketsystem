<?php namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;

use App\Http\Requests;
use ApiResponse;
use Validator;
use Auth;

class StatsController extends Controller
{
    public function contact(Request $request)
    {
        $response = [];

        $validator = \Validator::make($request->all(), [
            'contact_id' => 'required|array'
        ]);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        $contacts = $this->site->contacts()->whereIn('id', $request->get('contact_id'))->get();
        foreach ($contacts as $contact)
        {
            $response []= [
                'contact_id' => $contact->id,
                'tickets' => [
                    'codes' => $contact->tickets()->with('status')->groupBy('status_id')->select('*', \DB::raw('count(*) as total'))->get()->lists('total', 'status.code')->toArray(),
                    'states' => $contact->tickets()->join('ticket_statuses', 'status_id', '=', 'ticket_statuses.id')->groupBy('ticket_statuses.state')->select('ticket_statuses.state', \DB::raw('count(*) as total'))->get()->lists('total', 'state')->toArray(),
                ],
                'items' => $contact->items()->groupBy('type')->select('*', \DB::raw('count(*) as total'))->get()->lists('total', 'type')->toArray(),
            ];
        }

        return ApiResponse::success($response);
    }

    public function item(Request $request)
    {
        $response = [];

        $validator = \Validator::make($request->all(), [
            'item_id' => 'required|array'
        ]);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        $items = $this->site->items()->whereIn('id', $request->get('item_id'))->get();
        foreach ($items as $item)
        {
            $response []= [
                'item_id' => $item->id,
                'tickets' => [
                    'codes' => $item->tickets()->with('status')->groupBy('status_id')->select('*', \DB::raw('count(*) as total'))->get()->lists('total', 'status.code')->toArray(),
                    'states' => $item->tickets()->join('ticket_statuses', 'status_id', '=', 'ticket_statuses.id')->groupBy('ticket_statuses.state')->select('ticket_statuses.state', \DB::raw('count(*) as total'))->get()->lists('total', 'state')->toArray(),
                ],
                'contacts' => $item->contacts()->count(),
            ];
        }

        return ApiResponse::success($response);
    }

    public function user(Request $request)
    {
        $response = [];

        $validator = \Validator::make($request->all(), [
            'user_id' => 'required|array'
        ]);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        $users = $this->site->users()->whereIn(\DB::raw('users.id'), $request->get('user_id'))->get();
        foreach ($users as $user)
        {
            $response []= [
                'user_id' => $user->id,
                'tickets' => [
                    'codes' => $user->tickets()->with('status')->groupBy('status_id')->select('*', \DB::raw('count(*) as total'))->get()->lists('total', 'status.code')->toArray(),
                    'states' => $user->tickets()->join('ticket_statuses', 'status_id', '=', 'ticket_statuses.id')->groupBy('ticket_statuses.state')->select('ticket_statuses.state', \DB::raw('count(*) as total'))->get()->lists('total', 'state')->toArray(),
                ],
                'contacts' => [
                    'codes' => $user->tickets()->with('status')->groupBy('status_id')->select('*', \DB::raw('count(DISTINCT contact_id) as total'))->get()->lists('total', 'status.code')->toArray(),
                    'states' => $user->tickets()->join('ticket_statuses', 'status_id', '=', 'ticket_statuses.id')->groupBy('ticket_statuses.state')->select('ticket_statuses.state', \DB::raw('count(DISTINCT contact_id) as total'))->get()->lists('total', 'state')->toArray(),
                ],
                'items' => $user->items()->groupBy('type')->select('*', \DB::raw('count(*) as total'))->get()->lists('total', 'type'),
            ];
        }

        return ApiResponse::success($response);
    }

}
