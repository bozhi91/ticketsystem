<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Eloquent\Validator as EloquentValidator;

class Item extends Model
{
    use EloquentValidator;

    protected $hidden = ['created_at','updated_at'];

    protected $casts = ['data' => 'array'];

    /**
     * The attributes that should be visible for arrays.
     *
     * @var array
     */
    protected $visible = [
        'id', 'reference', 'type', 'title', 'image', 'url', 'data', 'contacts'
    ];

    protected static $create_validator_fields = [
        'site_id' => 'required|exists:sites,id',
        'reference' => 'required',
        'type' => 'required',
        'title' => 'required',
        'image' => 'url',
        'url' => 'url',
    ];

    protected static $update_validator_fields = [
        'site_id' => 'required|exists:sites,id',
        'reference' => '',
        'type' => '',
        'title' => '',
        'image' => 'url',
        'url' => 'url',
    ];

    public function users()
    {
        return $this->belongsToMany('App\User')->withTimestamps();
    }

    public function contacts()
    {
        return $this->belongsToMany('App\Contact')->withTimestamps();
    }

    public function site()
    {
        return $this->belongsTo('App\Site');
    }

    public function tickets()
    {
        return $this->hasMany('App\Ticket');
    }

}
