<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;

class PublicCourseEditionController extends PublicCoursePageController
{
    public function show(Request $request, string $public_token)
    {
        $edition = $this->edition($public_token);

        return $this->page($request, $edition->course, $edition);
    }

    public function register(Request $request, string $public_token)
    {
        $edition = $this->edition($public_token);

        return $this->submit($request, $edition->course, $edition);
    }
}
