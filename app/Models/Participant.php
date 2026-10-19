<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Participant extends Model
{
    protected $fillable = [
        'dependency_id',
        'dependency_name',
        'first_name',
        'first_last_name',
        'second_last_name',
        'curp',
        'area',
        'position',
        'institutional_email',
        'phone',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }


    public function dependency(): BelongsTo
    {
        return $this->belongsTo(Dependency::class, 'dependency_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'participant_id');
    }
}
