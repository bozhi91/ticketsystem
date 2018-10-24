<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Eloquent\Validator as EloquentValidator;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use SoftDeletes;
    use EloquentValidator {
        EloquentValidator::getValidator as parentGetValidator;
    }

    protected $hidden = ['updated_at'];

    /**
     * The attributes that should be visible for arrays.
     *
     * @var array
     */
    protected $visible = [
        'id', 'email', 'fullname', 'company', 'phone', 'address', 'locale', 'image', 'referer', 'created_at'
    ];

    protected static $create_validator_fields = [
        'site_id' => 'required|exists:sites,id',
        'email' => 'required|unique:contacts',
        'fullname' => '',
        'locale' => 'exists:locales,locale',
        'image' => 'url'
    ];

    protected static $update_validator_fields = [
        'site_id' => 'required|exists:sites,id',
        'reference' => '',
        'type' => '',
        'title' => '',
        'image' => 'url',
    ];

    public function site()
    {
        return $this->belongsTo('App\Site');
    }

    public function locale()
    {
        return $this->belongsTo('App\Locale');
    }

    public function tickets()
    {
        return $this->hasMany('App\Ticket');
    }

    public function items()
    {
        return $this->belongsToMany('App\Item')->withTimestamps();
    }

    public function setLocaleByCode($code)
    {
        $locale = \App\Locale::whereLocale($code)->first();
        if (!$locale)
        {
            return false;
        }

        $this->locale_id = $locale->id;

        return $this->save();
    }

    public static function saveModel($data, $id = null)
    {
        $fields = ['site_id', 'email', 'fullname', 'company', 'phone', 'phone', 'address', 'image', 'notes', 'referer'];

        if ($id)
        {
            $item = \App\Contact::find($id);
            if (!$item)
            {
                return false;
            }
        }
        else
        {
            $item = new \App\Contact;
        }

        foreach ($fields as $field)
        {
            if (isset($data[$field]))
            {
                $item->$field = $data[$field];
            }
        }
        $item->save();

        if (isset($data['locale']))
        {
            $item->setLocaleByCode($data['locale']);
        }

        return $item;
    }

    public static function getValidator($data, $exclude = [], $rules = [])
    {
        // Check if the email does not exists for the current site
        if (isset($data['site_id']))
        {
            $rules['email'] = 'required|email|!exists:contacts,email,site_id,'.$data['site_id'].',deleted_at,NULL';
        }

        return static::parentGetValidator($data, $exclude, $rules);
    }

}
