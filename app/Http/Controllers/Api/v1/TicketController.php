<?php namespace App\Http\Controllers\Api\v1;

use Illuminate\Http\Request;

use App\Http\Requests;
use ApiResponse;
use Validator;
use Auth;

class TicketController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->all();
        if (isset($data['status']))
        {
            $data['status'] = explode(',', $data['status']);
        }

        $validator = \Validator($data, [
            'contact_email' => 'email',
            'status' => 'array|in:open,closed,waiting,resolved',
            'from' => 'date_format:Y-m-d',
            'to' => 'date_format:Y-m-d',
            'orderby' => 'required_with:order|in:created_at,reference,referer,messages.count,messages.last,contact.fullname,contact.email,source,status,user.name',
            'order' => 'in:asc,desc',
            'limit' => 'integer|min:1',
            'page' => 'integer|min:1'
        ]);

        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        $limit = isset($data['limit']) ? $data['limit'] : 50;

        $query = $this->site->tickets()->withEverything();

        // Filters
        if (isset($data['contact_id']))
        {
            $query->where('contact_id', $data['contact_id']);
        }

        if (isset($data['contact_email']))
        {
            $query->whereHas('contact', function ($query) use ($data) {
                $query->where('email', $data['contact_email']);
            });
        }

        if (isset($data['user_id']))
        {
            if (!is_array($data['user_id']))
            {
                $data['user_id'] = [$data['user_id']];
            }

            $query->where(function ($query) use ($data)
            {
                $user_ids = $data['user_id'];

                if (in_array('null', $user_ids))
                {
                    $query->orWhereNull('tickets.user_id');

                    unset($user_ids[array_search('null', $user_ids)]);
                }

                if (!empty($user_ids))
                {
                    $query->orWhereIn('tickets.user_id', $user_ids);
                }
            });
        }

        if (isset($data['status']))
        {
            $query->whereHas('status', function ($query) use ($data) {
                $query->whereIn('code', $data['status']);
            });
        }

        if (isset($data['from']))
        {
            $query->where('created_at', '>=', $data['from']);
        }

        if (isset($data['to']))
        {
            $query->where('created_at', '<=', $data['to']);
        }

        if (isset($data['orderby']))
        {
            $ids = null;

            switch ($data['orderby']) {
                case 'messages.count':
                    $query->leftJoin('messages', 'messages.ticket_id', '=', 'tickets.id')
                            ->groupBy('tickets.id')
                            ->orderBy(\DB::raw('COUNT(messages.id)'), $data['order']);
                    break;

                case 'messages.last':
                    $query->leftJoin('messages', 'messages.ticket_id', '=', 'tickets.id')
                            ->groupBy('tickets.id')
                            ->orderBy(\DB::raw('MAX(messages.created_at)'), $data['order']);
                    break;

                case 'contact.fullname':
                case 'contact.email':
                    $query->leftJoin('contacts', 'contacts.id', '=', 'tickets.contact_id')
                            ->orderBy(str_replace('contact', 'contacts', $data['orderby']), $data['order']);
                    break;

                case 'user.name':
                    $query->leftJoin('users', 'users.id', '=', 'tickets.user_id')
                            ->orderBy(str_replace('user', 'users', $data['orderby']), $data['order']);
                    break;

                case 'source':
                    $query->leftJoin('ticket_sources', 'ticket_sources.id', '=', 'tickets.source_id')
                            ->orderBy('ticket_sources.name', $data['order']);
                    break;

                case 'status':
                    $query->leftJoin('ticket_statuses', 'ticket_statuses.id', '=', 'tickets.status_id')
                            ->orderBy('ticket_statuses.sort', $data['order']);
                    break;

                default:
                    $query->orderBy($data['orderby'], $data['order']);
                    break;
            }
        }

        // Get the tickets info
        $items = $query->select('tickets.*')->paginate($limit);

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

        try
        {
            $item = \App\Ticket::create($data);
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

        // Notify the user of a new ticket
        $item->notifyUser();

        // If we have a contact, we notify him
        if (!empty($data['contact_id']))
        {
            $account = null;
            // Fetch the user account
            if (!empty($data['email_account_id']))
            {
                $account = $this->user->email_accounts($this->site->id)->find($data['email_account_id']);
            }

            $item->messages()->first()->notifyContact($account);
        }

        return ApiResponse::created(['id' => $item->id], route('api.v1.ticket.show', $item->id));
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
        $item = $this->site->tickets()->find($id);
        if (!$item)
        {
            return ApiResponse::notFound();
        }

        $data = $request->all();

        $validator = \App\Ticket::getUpdateValidator($data, $id);
        if ($validator->fails())
        {
            return ApiResponse::badRequest($validator->errors()->first());
        }

        if (isset($data['status']))
        {
            $item->setStatusByCode($data['status']);
        }

        foreach (\App\Ticket::getUpdateValidatorFields($id) as $key => $value)
        {
            if ($key == 'status') continue;

            if (!empty($data[$key]))
            {
                $item->$key = $data[$key];
            }
        }

        $item->save();

        return ApiResponse::updated(route('api.v1.ticket.show', $item->id));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $item = $this->site->tickets()->withEverything()->find($id);
        if (!$item)
        {
            return ApiResponse::notFound();
        }

        return ApiResponse::success($item);
    }

    /**
     * Deletes the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = $this->site->tickets()->find($id);
        if (!$item)
        {
            return ApiResponse::notFound();
        }

        $item->delete();

        return ApiResponse::deleted();
    }

    /**
     * Displays tickets posible statuses.
     *
     * @return \Illuminate\Http\Response Posible ticket statuses
     */
    public function status()
    {
        $items = \App\TicketStatus::sorted()->get();
        return ApiResponse::success($items);
    }

    /**
     * Displays tickets posible sources.
     *
     * @return \Illuminate\Http\Response Posible ticket sources
     */
    public function source()
    {
        $items = \App\TicketSource::sorted()->get();
        return ApiResponse::success($items);
    }

}
