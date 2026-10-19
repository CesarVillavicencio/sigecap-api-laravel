<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEditionInstructor extends Model
{
    protected $fillable = [
        'course_edition_id',
        'instructor_id',
        'module_id',
        'topic',
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

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class, 'instructor_id');
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseEditionModule::class, 'module_id');
    }
}
