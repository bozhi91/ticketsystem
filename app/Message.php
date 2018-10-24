<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Eloquent\Validator as EloquentValidator;
use TeamTeaTime\Filer\HasAttachments;

class Message extends Model
{
    use EloquentValidator;
    use HasAttachments;

    /**
    * The attributes that should be visible for arrays.
    *
    * @var array
    */
    protected $visible = ['user', 'subject', 'body', 'source', 'referer', 'private', 'created_at', 'files'];

    protected $fillable = ['ticket_id', 'user_id', 'subject', 'body', 'signature', 'private', 'message_id', 'cc', 'bcc'];

    protected $appends = ['files'];

    protected $casts = [
        'cc' => 'array',
        'bcc' => 'array'
    ];

    protected static $create_validator_fields = [
        'ticket_id' => 'required|exists:tickets,id',
        'user_id' => 'exists:users,id',
        'source' => 'required|exists:ticket_sources,code',
        'subject' => 'required|string',
        'body' => 'required|string'
    ];

    protected static $update_validator_fields = [
        'ticket_id' => 'exists:tickets,id',
        'user_id' => 'exists:users,id',
        'subject' => 'string',
        'body' => 'string',
        'cc' => 'array',
        'bcc' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function ticket()
    {
        return $this->belongsTo('App\Ticket');
    }

    public function site()
    {
        return $this->ticket->belongsTo('App\Site');
    }

    public function source()
    {
        return $this->belongsTo('App\TicketSource');
    }

    public static function create(Array $attributes = [])
    {
        $item = parent::create($attributes);

        if ($item)
        {
            if (isset($attributes['source']))
            {
                $item->setSourceByCode($attributes['source']);
            }

            if (isset($attributes['attachments']) && is_array($attributes['attachments']))
            {
                foreach ($attributes['attachments'] as $attachment)
                {
                    $attachment['user_id'] = $attributes['user_id'];
                    $origin = !empty($attachment['url']) ? $attachment['url'] : $attachment['filename'];

                    $item->attach($origin, $attachment);
                }
            }
        }

        return $item;
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
        return $query->with('user')
                    ->with('source')
                    ->with('attachments')
                    ;
    }

    public function notifyContact(\App\EmailAccount $account = null)
    {
        // Never notify private notes
        if ($this->private)
        {
            return false;
        }

        // We have who to notify?
        if (!$this->ticket->contact)
        {
            return false;
        }

        // Use general site account
        if (!$account)
        {
            $account = $this->site->sendAccount();
        }

        // If no account then we have nothing to do here
        if (!$account)
        {
            return false;
        }

        $email = \App\Email::create([
            'email_account_id' => $account->id,
            'message_id' => $this->id,
            'to' => $this->ticket->contact->email,
            'cc' => $this->cc,
            'bcc' => $this->bcc,
            'subject' => 'RE: '.$this->subject,
            'body' => $this->signedBody,
            'attachments' => $this->getAttachmentsForMail()
        ]);

        // Send the email
        $account->send($email);

        return true;
    }

    public function notifyUser()
    {
        if (!$this->ticket->user)
        {
            return false;
        }

        $email = \App\Email::create([
            'message_id' => $this->id,
            'to' => $this->ticket->user->email,
            'subject' => 'Response received: '.$this->subject,
            'body' => $this->body,
            'attachments' => $this->getAttachmentsForMail()
        ]);

        $email = $this->site->sendMail($email);

        return $email->isSent();
    }

    public function getFilesAttribute()
    {
        $attachments = $this->attachments()->get();
        if ($attachments->isEmpty())
        {
            return [];
        }

        $result = $attachments->map(function($item)
        {
            return [
                'title' => $item->title,
                'url' => $item->getUrl()
            ];
        });

        return $result;
    }

    public function getAttachmentsForMail()
    {
        $attachments = [];

        $files = $this->attachments()->get();
        foreach ($files as $file)
        {
            $attachments []= [
                'url' => $file->getUrl(),
                'name' => $file->title,
                'id' => $file->key,
                'disposition' => strtolower($file->description)
            ];
        }

        return $attachments;
    }

    public function getSignedBodyAttribute()
    {
        return $this->body.$this->signature;
    }

}
