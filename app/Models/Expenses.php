<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Expenses extends Model
{
    //
    protected $fillable = ['name', 'description', 'amount','start_date','end_date','status','organization_id','recurring_id'];

    public $timestamps = false;
}
