<?php

namespace App\Models;

use App\Models\Expenses;
use Illuminate\Database\Eloquent\Model;

class Recurring extends Model
{
    //
    protected $fillable = ['name', 'description', 'start_date','end_date'];

    public $timestamps = false;

    public function expenses()
    {
        return $this->belongsTo(Expenses::class);
    }
}
