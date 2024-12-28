<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    protected $fillable = [
        'name',
        'short_description',
        'full_description',
        'category_id',
        'author_id',
        'views',
        'rating',
    ];
}
