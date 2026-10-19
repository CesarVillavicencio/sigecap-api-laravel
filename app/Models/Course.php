<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Course extends Model
{
    protected $fillable = [
        'created_by',
        'created_by_responsibility_id',
        'name',
        'activity',
        'description',
        'objective',
        'target_audience',
        'duration_hours',
        'modality',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'duration_hours' => 'decimal:2',
        ];
    }


    public function editions(): HasMany
    {
        return $this->hasMany(CourseEdition::class, 'course_id');
    }
}
