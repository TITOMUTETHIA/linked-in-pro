<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Http\Controllers\Controller;

class TagController extends Controller
{
    public function __invoke(Tag $tag)
    {
        //jobs for this tag
        $jobs = []; // Assign an empty array or fetch the jobs from a data source
        return view('results', ['jobs' => $jobs]);
    
    }
}
