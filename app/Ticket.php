<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Eloquent\Validator as EloquentValidator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use EloquentValidator;
    use SoftDeletes;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['deleted_at'];

    /**
    * The attributes that should be visible for arrays.
    *
    * @var array
    */
    protected $visible = ['id', 'contact', 'user', 'messages', 'reference', 'status', 'source', 'item', 'referer', 'created_at'];

    protected static $create_validator_fields = [
        'site_id' => 'required|exists:sites,id',
        'contact_id' => 'required|exists:contacts,id',
        'user_id' => 'exists:users,id',
        'item_id' => 'exists:items,id',
        'source' => 'required|exists:ticket_sources,code',
        'subject' => 'required',
        'body' => 'required'
    ];

    protected static $update_validator_fields = [
        'status' => 'exists:ticket_statuses,code',
        'user_id' => 'exists:users,id',
        'item_id' => 'exists:items,id',
    ];

    public static function boot()
    {
        parent::boot();

        // Whenever a Ticket is created in the database, we generate the reference
        static::creating(function($item)
        {
            if (!$item->site_id)
            {
                throw new \UnexpectdValueException('site_id can not be empy on ticket creation.');
            }

            // Get last number and add one
            $last_reference = Ticket::where('site_id', $item->site_id)->orderBy('reference', 'desc')->pluck('reference')->first();
            $item->reference = intval($last_reference) + 1;
        });
    }

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function contact()
    {
        return $this->belongsTo('App\Contact');
    }

    public function site()
    {
        return $this->belongsTo('App\Site');
    }

    public function messages()
    {
        return $this->hasMany('App\Message');
    }

    public function status()
    {
        return $this->belongsTo('App\TicketStatus');
    }

    public function source()
    {
        return $this->belongsTo('App\TicketSource');
    }

    public function item()
    {
        return $this->belongsTo('App\Item');
    }

    public static function create(array $data = [])
    {
        $validator = \App\Ticket::getValidator($data, null, ["contact_id" => "required|exists:contacts,id,site_id,{$data['site_id']}"]);
        if ($validator->fails())
        {
            throw new ValidationException($validator);
        }

        // Validate CC/BCC email address
        if (!empty($data['cc'])) {
            if (!\App\Email::validateEmails($data['cc'])) {
                $validator->getMessageBag()->add('cc', 'cc has an invalid email.');
                throw new ValidationException($validator);
            }
        }

        if (!empty($data['bcc'])) {
            if (!\App\Email::validateEmails($data['bcc'])) {
                $validator->getMessageBag()->add('bcc', 'bcc has invalid email.');
                throw new ValidationException($validator);
            }
        }

        // Check if we have to assign the ticket to a existing user
        if (empty($data['user_id'])) {
            $user = false;
            $site = \App\Site::find($data['site_id']);
            if (!$site) {
                $validator->getMessageBag()->add('site_id', 'site_id is not valid.');
                throw new ValidationException($validator);
            }

            // Find if the contact has a user assigned
            $contact = $site->contacts()->find($data['contact_id']);
            $ticket = $contact->tickets()->where('site_id', $site->id)->whereNotNull('user_id')->orderBy('created_at', 'desc')->first();
            if ($ticket) {
                $user = $ticket->user;
            }

            // Assign the ticket randomly to an agent?
            if ((!$user || !$user->enabled) && $site->assign_tickets == 'random') {
                $user = $site->users()->where('type', 'agent')->where('enabled', 1)->orderByRaw("RAND()")->first();
                if (!$user) {
                    $user = $site->users()->where('type', 'manager')->where('enabled', 1)->orderByRaw("RAND()")->first();
                }
            }

            if ($user && $user->enabled && $user->sites()->find($site->id)){
                $data['user_id'] = $user->id;
            }
        }

        // Create the ticket
        $item = new \App\Ticket;
        $item->site_id = $data['site_id'];
        $item->contact_id = !empty($data['contact_id']) ? $data['contact_id'] : null;
        $item->user_id = !empty($data['user_id']) ? $data['user_id'] : null;
        $item->item_id = !empty($data['item_id']) ? $data['item_id'] : null;
        $item->referer = !empty($data['referer']) ? $data['referer'] : null;
        $item->save();

        // Set as open by default
        $item->setStatusByCode('open');

        // Set the source
        $item->setSourceByCode($data['source']);

        // Create the message attached to the ticket
        $message = \App\Message::create([
            'ticket_id' => $item->id,
            'source' => $data['source'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'signature' => !empty($data['signature']) ? $data['signature'] : null,
            'user_id' => !empty($data['user_id']) ? $data['user_id'] : null,
            'referer' => !empty($data['referer']) ? $data['referer'] : null,
            'message_id' => !empty($data['message_id']) ? $data['message_id'] : null,
            'attachments' => !empty($data['attachments']) ? $data['attachments'] : null,
            'cc' => !empty($data['cc']) ? $data['cc'] : null,
            'bcc' => !empty($data['bcc']) ? $data['bcc'] : null,
        ]);

        return $item;
    }

    public function addMessage($data)
    {
        $data['ticket_id'] = $this->id;

        $validator = \App\Message::getValidator($data, null, ["user_id" => "required|exists:site_user,user_id,site_id,{$this->site_id}"]);
        if ($validator->fails())
        {
            throw new ValidationException($validator);
        }

        // Validate CC/BCC email address
        if (!empty($data['cc'])) {
            if (!\App\Email::validateEmails($data['cc'])) {
                $validator->getMessageBag()->add('cc', 'cc has an invalid email.');
                throw new ValidationException($validator);
            }
        }

        if (!empty($data['bcc'])) {
            if (!\App\Email::validateEmails($data['bcc'])) {
                $validator->getMessageBag()->add('bcc', 'bcc has invalid email.');
                throw new ValidationException($validator);
            }
        }

        $data = [
            'ticket_id' => $data['ticket_id'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'signature' => !empty($data['signature']) ? $data['signature'] : null,
            'user_id' => $data['user_id'],
            'source' => $data['source'],
            'private' => !empty($data['private']) ? $data['private'] : 0,
            'attachments' => !empty($data['attachments']) ? $data['attachments'] : [],
            'cc' => !empty($data['cc']) ? $data['cc'] : null,
            'bcc' => !empty($data['bcc']) ? $data['bcc'] : null,
        ];

        $item = \App\Message::create($data);
        if (!$item)
        {
            throw new \UnexpectedValueException("Error creating the message.");
        }

        return $item;
    }

    public function addContactMessage($data)
    {
        $data['ticket_id'] = $this->id;

        $validator = \App\Message::getValidator($data, ['user_id']);
        if ($validator->fails())
        {
            throw new ValidationException($validator);
        }

        $data = [
            'ticket_id' => $data['ticket_id'],
            'subject' => $data['subject'],
            'body' => $data['body'],
            'source' => $data['source'],
            'private' => 0,
            'message_id' => !empty($data['message_id']) ? $data['message_id'] : null
        ];

        $item = \App\Message::create($data);
        if (!$item)
        {
            throw new \UnexpectedValueException("Error creating the message.");
        }

        // If ticket closed, whe reopen it
        if ($this->isClosed())
        {
            $this->setStatusByCode('open');
        }

        return $item;
    }

    public function setStatusByCode($code)
    {
        $status = \App\TicketStatus::whereCode($code)->first();
        if (!$status)
        {
            return false;
        }

        $this->status_id = $status->id;

        return $this->save();
    }

    public function setSourceByCode($code)
    {
        $source = \App\TicketSource::whereCode($code)->first();
        if (!$source)
        {
            return false;
        }

        $this->source_id = $source->id;

        return $this->save();
    }

    public function scopeWithEverything($query)
    {
        return $query->with('contact')
                    ->with('user')
                    ->with('messages.user')
                    ->with('messages.source')
                    ->with('status')
                    ->with('source')
                    ->with('item')
                    ->with([ 'messages' => function($query) {
                        $query->orderBy('created_at', 'desc');
                    }])
                    ;
    }

    public function notifyUser()
    {
        if (!$this->user_id)
        {
            return false;
        }

        // The first message always for the new ticket notification
        $message = $this->messages()->orderBy('created_at', 'asc')->first();

        $email = \App\Email::create([
            'message_id' => $message->id,
            'to' => $this->user->email,
            'cc' => $message->cc,
            'bcc' => $message->bcc,
            'subject' => 'New Ticket Received - '.$message->subject,
            'body' => $message->body,
            'attachments' => $message->getAttachmentsForMail(),
        ]);

        $email = $this->site->sendMail($email);

        return $email ? $email->isSent() : false;
    }

    public function isClosed()
    {
        return $this->status && $this->status->state == 'closed';
    }

}
