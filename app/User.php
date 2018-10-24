<?php namespace App;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Traits\Eloquent\Validator as EloquentValidator;

class User extends Authenticatable
{
    use EloquentValidator;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password', 'site_id', 'enabled'
    ];

    protected $appends = ['type'];

    /**
     * The attributes that should be visible for arrays.
     *
     * @var array
     */
    protected $visible = [
        'id', 'name', 'email', 'type', 'email_accounts'
    ];

    protected static $create_validator_fields = [
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'site_id' => 'required|exists:sites,id',
        'type' => 'required|in:guest,agent,manager'
    ];

    protected static $update_validator_fields = [
        'name' => '',
        'email' => 'email|unique:users,email,:current',
        'type' => 'in:guest,agent,manager'
    ];

    public static function boot()
    {
        parent::boot();

        // Whenever a User is created in the database, we add a token
        static::creating(function($user){
            $user->api_token = str_random(60);
        });
    }

    public function sites()
    {
        return $this->belongsToMany('App\Site')->withTimestamps()->withPivot('type');
    }

    public function items()
    {
        return $this->belongsToMany('App\Item')->withTimestamps();
    }

    public function tickets()
    {
        return $this->hasMany('App\Ticket');
    }

    public function contacts()
    {
        return $this->belongsToMany('App\Contact', 'tickets', 'user_id', 'contact_id')->withTimestamps()->groupBy('contact_id');
    }

    public function email_accounts($site_id = null)
    {
        $query = $this->hasMany('App\EmailAccount');
        if ($site_id) {
            $query->site($site_id);
        }

        return $query;
    }

    public function scopeEnabled($query)
    {
        return $query->whereEnabled(1);
    }

    public function getTypeAttribute()
    {
        if (!$this->pivot)
        {
            return null;
        }

        return $this->pivot->type;
    }

    public function reasign(User $user, $site_id)
    {
        // Log
        $log = new Logger('reasign');
        $log->pushHandler(new StreamHandler(storage_path('logs/reasign.log'), Logger::INFO));

        // Tickets to update
        $tickets = $this->tickets()->where('site_id', $site_id);

        // Perform the update
        $log->addInfo("Reasing from $this->id => $user->id: tickets", $tickets->get()->lists('id')->toArray());
        $tickets->update(['user_id' => $user->id]);

        return true;
    }

}
