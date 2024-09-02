<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Tag;
use App\Http\Requests\StoreJobRequest;

use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Arr;

class JobController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $jobs = job::latest()->with(['employer', 'tags'])->get()->groupBy('featured');
        

        return view('job.index', [
            'jobs' => $jobs  ,
            'featuredJobs' =>$jobs->has(1) ? $jobs[1] : collect(),
            'tags' => Tag::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('job.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreJobRequest $request)
    {
        $attributes = $request->validate([
           'title' => ['required'],
           'salary' => ['required'],
           'location' => ['required'],
           'schedule' => ['required', Rule::in(['Part Time', 'Full Time'])],
           'url' => ['required', 'active_url'],
           'featured' => ['boolean'],
        ]);

        $attributes['featured'] = $request->has('featured');
        $job = Auth::user()->employer->jobs()->create([Arr::except($attributes, 'tags')]); 
        if ($attributes['tags'] ?? false) {
            foreach (explode(',', $attributes['tags']) as $tag){
                $job->tag($tag);
            }

        }
        return redirect('/');

    }

}  