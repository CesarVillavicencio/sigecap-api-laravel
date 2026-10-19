<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dependency extends Model
{
    protected $fillable = [
        'name',
        'acronym',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }


    public function participants(): HasMany
    {
        return $this->hasMany(Participant::class, 'dependency_id');
    }
}
