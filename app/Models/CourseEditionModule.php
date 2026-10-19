<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseEditionModule extends Model
{
    protected $fillable = [
        'course_edition_id',
        'name',
        'description',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
        ];
    }


    public function courseEdition(): BelongsTo
    {
        return $this->belongsTo(CourseEdition::class, 'course_edition_id');
    }

    public function instructorAssignments(): HasMany
    {
        return $this->hasMany(CourseEditionInstructor::class, 'module_id');
    }
}
