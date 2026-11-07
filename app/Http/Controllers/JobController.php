<?php

namespace App\Http\Controllers;

use App\Http\Requests\JobRequest;
use App\Models\Job;
use App\Models\Skill;
use App\Services\JobService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class JobController extends Controller
{
    public function index(Request $r)
    {
        return view('jobs.index', ['jobs' => Job::where('client_id', $r->user()->id)->latest()->paginate(10), 'managing' => true]);
    }

    public function browse()
    {
        return view('jobs.index', ['jobs' => Job::where('status', 'published')->latest()->paginate(10), 'managing' => false]);
    }

    public function create()
    {
        return view('jobs.form', ['job' => new Job, 'skills' => Skill::orderBy('name')->get()]);
    }

    public function store(JobRequest $r, JobService $service)
    {
        $job = $service->save($r->user(), $r->validated());

        return redirect()->route('jobs.show', $job)->with('success', 'Draft created.');
    }

    public function show(Job $job)
    {
        Gate::authorize('view', $job);

        return view('jobs.show', compact('job'));
    }

    public function edit(Job $job)
    {
        Gate::authorize('update', $job);
        abort_unless($job->editable(), 403, 'Editing is locked.');

        return view('jobs.form', ['job' => $job, 'skills' => Skill::orderBy('name')->get()]);
    }

    public function update(JobRequest $r, Job $job, JobService $service)
    {
        Gate::authorize('update', $job);
        $service->save($r->user(), $r->validated(), $job);

        return redirect()->route('jobs.show', $job)->with('success', 'Posting updated.');
    }

    public function publish(Job $job, JobService $service)
    {
        Gate::authorize('update', $job);
        $service->transition($job, 'published');

        return back()->with('success', 'Posting published.');
    }

    public function close(Job $job, JobService $service)
    {
        Gate::authorize('update', $job);
        $service->transition($job,'closed');

        return back()->with('success','Posting closed.');
    }
}
