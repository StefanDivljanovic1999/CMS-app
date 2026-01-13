<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PostCategory extends Model
{
    protected $fillable=[
        'title',
        'slug',
        'context'
    ];

    public function posts()
{
    return $this->hasMany(Post::class);
}

}
