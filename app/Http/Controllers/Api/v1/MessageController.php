<?php namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;

use App\Http\Requests;
use ApiResponse;
use Validator;
use Auth;

class MessageController extends Controller
{
    public function index(Request $request, \App\Ticket $ticket)
    {
        // Check if the ticket belongs to the site
        if ($ticket->site->id != $this->site->id)
        {
            return ApiResponse::notFound();
        }

        $items = $ticket->messages()->withEverything()->get();
        return ApiResponse::success($items);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, \App\Ticket $ticket)
    {
        // Check if the ticket belongs to the site
        if ($ticket->site->id != $this->site->id)
        {
            return ApiResponse::notFound();
        }

        $data = $request->all();

        if (!isset($data['user_id']))
        {
            $data['user_id'] = $this->user->id;
        }

        try
        {
            $item = $ticket->addMessage($data);

            // We try to notify the contact
            if ($item)
            {
                $account = null;
                // Fetch the user account
                if (!empty($data['email_account_id']))
                {
                    $account = $this->user->email_accounts($this->site->id)->find($data['email_account_id']);
                }

                $item->notifyContact($account);
            }
        }
        catch (\Illuminate\Validation\ValidationException $e)
        {
            return ApiResponse::badRequest($e->validator->errors()->first());
        }
        catch (\Exception $e)
        {
            // Log exception in the app log (storage/logs/laravel.log)
            \Log::error($e);

            return ApiResponse::internalServerError();
        }

        return ApiResponse::created(['id' => $item->id], route('api.v1.ticket.{ticket}.message.show', ['ticket' => $ticket->id, 'message' => $item->id]));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show(\App\Ticket $ticket, $id)
    {
        // Check if the ticket belongs to the site
        if ($ticket->site->id != $this->site->id)
        {
            return ApiResponse::notFound();
        }

        $message = $ticket->messages()->withEverything()->find($id);
        if (!$message)
        {
            return ApiResponse::notFound();
        }

        return ApiResponse::success($message);
    }

}
