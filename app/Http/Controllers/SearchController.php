<?php

namespace App\Http\Controllers;
use APP\Models\Job;
use App\Models\jobs;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function __invoke()
    {
         $job = Job::query()
         ->with(['employer', 'tags'])
         ->where('title', 'LIKE', '%'.request('q').'%')
         ->get();
        return view('results', ['jobs' => $job]);
    }
}
