<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['data' => 'array'];
    }

    public function seoable()
    {
        return $this->morphTo();
    }

    public function image()
    {
        return $this->belongsTo(Media::class);
    }
}
