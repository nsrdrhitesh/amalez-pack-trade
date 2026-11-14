<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use Concerns\HasContent;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'comments_enabled' => 'boolean'];
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'post_category');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function getUrlAttribute(): string
    {
        return url('/blog/'.$this->slug);
    }

    public function getReadingMinutesAttribute(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->body ?? '')) / 200));
    }
}
