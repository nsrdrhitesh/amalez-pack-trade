<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NavigationMenu extends Model
{
    protected $guarded = ['id'];

    public function items()
    {
        return $this->hasMany(NavigationItem::class)->orderBy('sort_order')->orderBy('id');
    }
}
