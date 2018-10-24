<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Eloquent\Validator as EloquentValidator;
use Illuminate\Validation\ValidationException;

class Site extends Model
{
    use EloquentValidator;

    protected $hidden = ['created_at','updated_at'];

    protected $visible = ['id', 'title'];

    protected static $create_validator_fields = [
        'title' => 'required',
    ];

    protected static $update_validator_fields = [
        'title' => '',
    ];

    public function users()
    {
        return $this->belongsToMany('App\User')->withTimestamps()->withPivot('type');
    }

    public function items()
    {
        return $this->hasMany('App\Item');
    }

    public function contacts()
    {
        return $this->hasMany('App\Contact');
    }

    public function tickets()
    {
        return $this->hasMany('App\Ticket');
    }

    public function email_accounts()
    {
        return $this->morphMany('App\EmailAccount', 'model')->whereNull('user_id');
    }

    public static function saveModel($data, $id = null)
    {
        $fields = ['title'];

        if ($id)
        {
            $item = \App\Site::find($id);
            if (!$item)
            {
                return false;
            }
        }
        else
        {
            $item = new \App\Site;
        }

        foreach ($fields as $field)
        {
            if (isset($data[$field]))
            {
                $item->$field = $data[$field];
            }
        }
        $item->save();

        if (isset($data['email_account']))
        {
            // If empty, we delete everything and do nothing more
            if (empty($data['email_account']))
            {
                $item->email_accounts()->delete();
            }
            else
            {
                foreach ($data['email_account'] as $protocol => $email_data)
                {
                    // If empty array, we delete the accounts for that protocol group (send/fetch)
                    if (empty($email_data))
                    {
                        $item->email_accounts()->protocol($protocol)->delete();
                    }
                    else
                    {
                        $email_data['protocol'] = $protocol;
                        $email_data['enabled'] = 1;

                        if (!$id)
                        {
                            $validator = \App\EmailAccount::getValidator($email_data);
                        }
                        else
                        {
                            $validator = \App\EmailAccount::getUpdateValidator($email_data, 0);
                        }

                        if ($validator->fails())
                        {
                            throw new ValidationException($validator);
                        }

                        // Create/update the account
                        $account = $item->email_accounts()
                            ->where('username', $email_data['username'])
                            ->where('protocol', $protocol)
                            ->first();
                        $account = \App\EmailAccount::saveModel($email_data, $account ? $account->id : null);
                        if (!$account)
                        {
                            throw new \UnexpectedValueException('Unable to create the email account.');
                        }

                        // We allow only one email for protocol group (fectch/send), if we have more than one, delete them and keep the last one
                        $item->email_accounts()->where('id', '!=', $account->id)->protocol($protocol)->delete();
                        $item->email_accounts()->save($account);
                    }
                }
            }
        }

        return $item;
    }

    public function sendMail(\App\Email $email)
    {
        // Account configured?
        $account = $this->sendAccount();
        if (!$account)
        {
            $email->setError('Send account not configured on this site.');
            return false;
        }

        $email = $account->send($email);

        return $email;
    }

    public function fetchAccount()
    {
        return $this->email_accounts()->enabled()->whereIn('protocol', ['pop3', 'imap', 'mailgun'])->first();
    }

    public function sendAccount()
    {
        return $this->email_accounts()->enabled()->where('protocol', 'smtp')->first();
    }

}
