<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use Concerns\HasContent;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function getUrlAttribute(): string
    {
        return $this->slug === 'home' ? url('/') : url('/'.$this->slug);
    }
}
