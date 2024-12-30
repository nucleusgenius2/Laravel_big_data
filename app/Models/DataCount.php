<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DataCount extends Model
{
    protected $fillable = [
        'type',
        'count'
    ];

    public $timestamps = false;
}
