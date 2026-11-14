<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentSection extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['settings' => 'array', 'enabled' => 'boolean'];
    }

    public function sectionable()
    {
        return $this->morphTo();
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }

    public function visible(): bool
    {
        return $this->enabled && (filled($this->title) || filled(strip_tags($this->body ?? '')) || $this->media_id);
    }
}
