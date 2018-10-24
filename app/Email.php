<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class Email extends Model
{
    protected $guarded = [];

    protected $casts = [
        'headers' => 'array',
        'attachments' => 'array',
        'cc' => 'array',
        'bcc' => 'array'
    ];

    public function contact()
    {
        return $this->belongsTo('App\Contact');
    }

    public function message()
    {
        return $this->belongsTo('App\Message');
    }

    public function sent($message_id)
    {
        $this->email_id = $message_id;
        $this->sent_at = \Carbon\Carbon::now();
        $this->save();
    }

    public function setError($message)
    {
        $this->error = $message;
        $this->save();
    }

    public function isSent()
    {
        return !empty($this->sent_at);
    }

    public static function validateEmails($emails)
    {
        if (!is_array($emails)) {
            return false;
        }

        foreach ($emails as $email) {
            if (!\Swift_Validate::email($email)) {
                return false;
            }
        }

        return true;
    }

}
