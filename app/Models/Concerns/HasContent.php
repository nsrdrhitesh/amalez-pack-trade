<?php

namespace App\Models\Concerns;

use App\Models\ContentSection;
use App\Models\Media;
use App\Models\MediaLink;
use App\Models\SeoMetadata;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasContent
{
    public function scopePublished(Builder $q): Builder
    {
        return $q->whereIn('status', ['published', 'scheduled'])->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function featuredMedia()
    {
        return $this->belongsTo(Media::class, 'featured_media_id');
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function sections()
    {
        return $this->morphMany(ContentSection::class, 'sectionable')->orderBy('sort_order')->orderBy('id');
    }

    public function seo()
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }

    public function gallery()
    {
        return $this->morphMany(MediaLink::class, 'mediable')->orderBy('sort_order')->orderBy('id');
    }

    public function isPublic(): bool
    {
        return in_array($this->status, ['published', 'scheduled'], true) && (! $this->published_at || $this->published_at->isPast());
    }
}
