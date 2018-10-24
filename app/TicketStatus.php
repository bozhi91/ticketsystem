<?php namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\Eloquent\Validator as EloquentValidator;

class TicketStatus extends Model
{
    use EloquentValidator;

    protected $table = 'ticket_statuses';

    protected $fillable = ['code', 'name', 'sort'];

    protected $visible = ['code', 'name'];

    public function tickets()
    {
        return $this->hasMany('App\Ticket');
    }

    public function scopeSorted($query)
    {
        return $query->orderBy('sort', 'asc');
    }

}
