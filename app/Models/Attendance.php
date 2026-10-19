<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'registration_id',
        'registered_by',
        'registered_by_responsibility_id',
        'check_in_at',
        'registration_method',
        'device',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'check_in_at' => 'datetime',
        ];
    }


    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class, 'registration_id');
    }
}
