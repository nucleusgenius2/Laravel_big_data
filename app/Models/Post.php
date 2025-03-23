<?php

namespace App\Models;


use App\Traits\FilterHandler;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use FilterHandler;

    /**
     * @var string[]
     */
    protected $fillable = [
        'name',
        'content',
        'short_description',
        'seo_title',
        'seo_description',
        'img',
        'category_id',
        'author',
    ];

    public $timestamps = false;

    protected array $whereStrong = ['rating', 'author_id'];
    protected array $whereSearch = ['name'];
    protected array $intervalSearch = ['created_at_from', 'created_at_to'];
    protected array $dateFixed = ['date_fixed'];
    //protected array $whereIn = ['author_id'];
}
