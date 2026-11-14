<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaLink extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    public function mediable()
    {
        return $this->morphTo();
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
