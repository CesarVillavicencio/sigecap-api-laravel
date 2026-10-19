<?php

namespace App\Http\Controllers\Api;

use App\Models\Course;
use Illuminate\Http\Request;

class PublicCourseController extends PublicCoursePageController
{
    public function index(Request $request)
    {
        $courses = Course::with('editions')->where('active', true)
            ->whereHas('editions', fn ($q) => $q->whereNotIn('status', ['draft', 'cancelled']))
            ->orderBy('name')->get(['id', 'name', 'description', 'modality', 'duration_hours']);
        
        return $request->expectsJson()
            ? response()->json(['data' => $courses])
            : response()->view('public-courses.index', compact('courses'));
    }

    public function show(Request $request, string $id)
    {
        return $this->page($request, $this->course($id));
    }

    public function register(Request $request, string $id)
    {
        return $this->submit($request, $this->course($id));
    }
}
