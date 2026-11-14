<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use Concerns\HasContent;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'featured' => 'boolean', 'confirmed' => 'boolean'];
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('title');
    }

    public function related()
    {
        return $this->belongsToMany(self::class, 'related_services', 'service_id', 'related_service_id');
    }

    public function ancestors(): array
    {
        $result = [];
        $node = $this;
        $seen = [];
        while ($node) {
            if (isset($seen[$node->id])) {
                throw new \LogicException('Circular service hierarchy');
            }$seen[$node->id] = true;
            array_unshift($result, $node);
            $node = $node->parent;
        }

        return $result;
    }

    public function getUrlAttribute(): string
    {
        return url('/services/'.implode('/', array_map(fn ($s) => $s->slug, $this->ancestors())));
    }

    public function isPublic(): bool
    {
        foreach ($this->ancestors() as $node) {
            if (! in_array($node->status, ['published', 'scheduled'], true) || ($node->published_at && $node->published_at->isFuture())) {
                return false;
            }
        }

        return true;
    }
}
