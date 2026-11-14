<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FormField extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['options' => 'array', 'enabled' => 'boolean', 'required' => 'boolean'];
    }
}
