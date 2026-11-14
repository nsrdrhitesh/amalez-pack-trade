<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['consented_at' => 'datetime'];
    }

    public function values()
    {
        return $this->hasMany(InquiryValue::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function field(string $key): string
    {
        return $this->values->firstWhere('key', $key)?->value ?? '';
    }
}
