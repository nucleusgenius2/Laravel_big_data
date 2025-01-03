<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Author extends Model
{
    protected $fillable = [
        'user_id',
        'post_id'
    ];

    public $timestamps = false;
    public $incrementing = false;
    protected $primaryKey = null;
}
