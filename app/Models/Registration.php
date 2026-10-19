<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Registration extends Model
{
    protected $fillable = [
        'participant_id',
        'course_edition_id',
        'folio',
        'qr_token',
        'status',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }


    public function participant(): BelongsTo
    {
        return $this->belongsTo(Participant::class, 'participant_id');
    }

    public function courseEdition(): BelongsTo
    {
        return $this->belongsTo(CourseEdition::class, 'course_edition_id');
    }

    public function attendance(): HasOne
    {
        return $this->hasOne(Attendance::class, 'registration_id');
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class, 'registration_id');
    }
}
