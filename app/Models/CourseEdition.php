<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseEdition extends Model
{
    protected $fillable = [
        'course_id',
        'created_by',
        'created_by_responsibility_id',
        'public_token',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'location',
        'max_capacity',
        'participant_target',
        'registration_start',
        'registration_end',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'registration_start' => 'datetime',
            'registration_end' => 'datetime',
            'max_capacity' => 'integer',
            'participant_target' => 'integer',
        ];
    }


    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(Registration::class, 'course_edition_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(CourseEditionModule::class, 'course_edition_id');
    }

    public function editionInstructors(): HasMany
    {
        return $this->hasMany(CourseEditionInstructor::class, 'course_edition_id');
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(Instructor::class, 'course_edition_instructors', 'course_edition_id', 'instructor_id')->withPivot('module_id', 'topic', 'display_order')->withTimestamps();
    }
}
