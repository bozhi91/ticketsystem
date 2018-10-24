<?php namespace App;

use Illuminate\Database\Eloquent\Model;

class TicketSource extends Model
{
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
