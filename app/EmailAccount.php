<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Eloquent\Validator as EloquentValidator;
use Illuminate\Validation\ValidationException;
use Swift_Mailer;

class EmailAccount extends Model
{
    use EloquentValidator {
        getValidator as originalGetValidator;
        getUpdateValidator as originalGetUpdateValidator;
    }

    protected $fillable = [
        'from_name', 'from_email', 'username', 'password', 'host', 'port', 'layer', 'protocol', 'enabled'
    ];

    protected $visible = [
        'id', 'from_name', 'from_email', 'username', 'host', 'port', 'layer', 'protocol'
    ];

    protected static $create_validator_fields = [
        'from_email' => 'email',
        'username' => 'required',
        'password' => 'required',
        'host' => 'required',
        'port' => '',
        'layer' => 'in:tls,ssl',
        'protocol' => 'required|in:smtp,imap,pop3,mailgun'
    ];

    protected static $update_validator_fields = [
        'from_email' => 'email',
        'username' => '',
        'password' => '',
        'host' => '',
        'port' => '',
        'layer' => 'in:tls,ssl',
        'protocol' => 'in:smtp,imap,pop3,mailgun'
    ];

    protected $mailbox = null;
    protected $mailer = null;

    public function model()
    {
        return $this->morphTo();
    }

    public function scopeEnabled($query)
    {
        $query->whereEnabled(1);
    }

    public function isFetchProtocol($protocol)
    {
        return in_array($protocol, $this->getFetchProtocols());
    }

    public function getFetchProtocols()
    {
        return ['imap', 'pop3', 'mailgun'];
    }

    public function isSendProtocol($protocol)
    {
        return in_array($protocol, $this->getSendProtocols());
    }

    public function getSendProtocols()
    {
        return ['smtp'];
    }

    public function scopeProtocol($query, $protocol)
    {
        if ($this->isFetchProtocol($protocol))
        {
            $query->whereIn('protocol', $this->getFetchProtocols());
        }
        else if ($this->isSendProtocol($protocol))
        {
            $query->whereIn('protocol', $this->getSendProtocols());
        }
        else
        {
            // Make sure we return nothing
            $query->where('protocol', 0);
        }

        return $query;
    }

    public static function saveModel($data, $id = null)
    {
        $fields = [
            'from_name', 'from_email', 'username', 'password', 'host', 'port', 'layer', 'protocol',
            'enabled', 'user_id'
        ];

        if ($id)
        {
            $item = \App\EmailAccount::find($id);
            if (!$item)
            {
                return false;
            }
        }
        else
        {
            $item = new \App\EmailAccount;
        }

        foreach ($fields as $field)
        {
            if (isset($data[$field]))
            {
                $item->$field = $data[$field];
            }
        }
        $item->save();

        return $item;
    }

    public function getPathAttribute()
    {
        $path = [$this->host.':'.$this->port, $this->protocol];
        if ($this->layer)
        {
            $path []= $this->layer;
        }

        $path []= 'novalidate-cert';

        return '{'.implode('/', $path).'}INBOX';
    }

    public function getCredentialsAttribute()
    {
        return [
            'path' => $this->path,
            'username' => $this->username,
            'password' => $this->password
        ];
    }

    public function setError($message)
    {
        $this->error = $message;
        $this->error_at = \Carbon\Carbon::now();
        $this->save();
    }

    public function getMailbox()
    {
        if (empty($this->mailbox))
        {
            $attachments_path = storage_path('emails/inbox/');

            switch ($this->protocol) {
                case 'mailgun':
                    $this->mailbox = new \App\PhpImap\Mailgun($this->username, $this->password, $attachments_path);
                    break;

                default:
                    $credentials = $this->credentials;
                    $this->mailbox = new \App\PhpImap\Mailbox($credentials['path'], $credentials['username'], $credentials['password'], $attachments_path);
                    break;
            }
        }

        return $this->mailbox;
    }

    public function searchMailbox($search)
    {
        // Get mailbox
        $mailbox = $this->getMailbox();

        // Set last connection
        $this->last_connection_at = \Carbon\Carbon::now();
        $this->save();

        return $mailbox->searchMailbox($search);
    }

    public function fetchNewEmails()
    {
        // Si $this->last_connection_at es null, se crea con la fecha actual
        $last_connection = \Carbon\Carbon::parse($this->last_connection_at);

        return $this->searchMailbox('SINCE '.$last_connection->format('j-M-Y'));
    }

    public function getMail($id)
    {
        return $this->getMailbox()->getMail($id);
    }

    public function getMailer()
    {
        if (!$this->mailer)
        {
            $transport = \Swift_SmtpTransport::newInstance($this->host, $this->port, $this->layer);
            $transport->setUsername($this->username);
            $transport->setPassword($this->password);
            $transport->setStreamOptions([
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]);
            $this->mailer = new Swift_Mailer($transport);
        }

        return $this->mailer;
    }

    /**
     * Sends email through this account.
     *
     * @param  App\Email $email
     * @return App\Email
     */
    public function send(\App\Email $email)
    {
        if ($this->protocol != 'smtp')
        {
            throw new \UnexpectedValueException("You can\'t send emails with a $this->protocol account.");
        }

        // Attach the email to this account
        $email->email_account_id = $this->id;
        $email->save();

        // Sende the email through a job
        dispatch(new \App\Jobs\SendEmail($email, $this));

        return $email;
    }

    /**
     * Tries to connect to the server.
     *
     * @return boolean
     */
    public function test_connection()
    {
        try
        {
            switch ($this->protocol)
            {
                case 'imap':
                case 'pop3':
                case 'mailgun':
                    $this->getMailbox()->testConnection();
                    break;

                case 'smtp':
                    $this->getMailer()->getTransport()->start();
                    break;

                default:
                    throw new \UnexpectedValueException("Protocol $this->protocol not implemented.");
                    break;
            }

            return true;
        }
        catch (\Exception $e)
        {
            $this->setError($e->getMessage());
        }

        return false;
    }

    public function scopeSite($query, $site_id)
    {
        return $query->where('model_type', 'App\Site')->where('model_id', $site_id);
    }

    public static function getValidator($data, $exclude = [], $rules = [])
    {
        $validator = static::originalGetValidator($data, $exclude, $rules);
        static::prepareValidator($validator);
        return $validator;
    }

    public static function getUpdateValidator($data, $current_id, $exclude = [])
    {
        $validator = static::originalGetUpdateValidator($data, $current_id, $exclude);
        static::prepareValidator($validator);
        return $validator;
    }

    protected static function prepareValidator(&$validator)
    {
        $validator->sometimes('port', 'required', function($input) {
            return $input->protocol != 'mailgun';
        });
    }

}
