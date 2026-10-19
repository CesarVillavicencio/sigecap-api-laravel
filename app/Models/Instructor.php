<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Instructor extends Model
{
    protected $fillable = [
        'name',
        'position',
        'institution',
        'email',
        'phone',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }


    public function editionAssignments(): HasMany
    {
        return $this->hasMany(CourseEditionInstructor::class, 'instructor_id');
    }

    public function courseEditions(): BelongsToMany
    {
        return $this->belongsToMany(CourseEdition::class, 'course_edition_instructors', 'instructor_id', 'course_edition_id')->withPivot('module_id', 'topic', 'display_order')->withTimestamps();
    }
}
