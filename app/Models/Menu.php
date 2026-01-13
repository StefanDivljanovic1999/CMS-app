<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Menu extends Model
{
        protected $fillable = ['title', 'items', 'user_id', 'slug'];

        //moramo da konvertujemo Json u array
        protected $casts = [
                'items' => 'array'
        ];

        public function pages()
        {
                return $this->belongsToMany(Page::class, 'menu_page', 'menu_id', 'page_id');
        }

        public function user()
        {
                return $this->belongsTo(User::class);
        }
}
