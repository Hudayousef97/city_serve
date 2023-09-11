<?php

namespace App\Http\Controllers\Jobs;

use App\Models\Job\Tasks;
use App\Models\Tasks\Task;
use App\Models\Job\JobSaved;
use Illuminate\Http\Request;
use App\Models\Job\Application;
use App\Models\Category\Category;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class JobsController extends Controller
{
    public function single($id)
    {
        $job = Task::with('category:id,name')->findOrFail($id);
        $relatedJobs = Task::where('category_id', $job->category_id)
            ->where('id', '!=', $id)
            ->take(5)
            ->get();

        $relatedJobsCount = $relatedJobs->count();
        $savedJob = null;
        $appliedJob = null;

        if (auth()->check()) {
            $user_id = auth()->user()->id;

            $savedJob = JobSaved::where('task_id', $id)
                ->where('user_id', $user_id)
                ->count();
            $appliedJob = Application::where('user_id', $user_id)
                ->where('task_id', $id)
                ->count();
        }
        $categories = Category::all()->take(5);

        return view('jobs.single', compact('job', 'relatedJobs', 'relatedJobsCount', 'savedJob', 'appliedJob', 'categories'));
    }

    public function saveJob(Request $request)
    {

        $saveJob = JobSaved::create([
            'task_id' => $request->task_id,
            'user_id' => $request->user_id,
        ]);
        if ($saveJob) {
            return redirect('/jobs/single/' . $request->task_id . '')->with('save', 'Oppertuinity saved!');
        }
    }


    public function jobApply(Request $request)
    {
        if ($request->cv == 'No cv' || $request->cv == null) {
            return redirect('/jobs/single/' . $request->job_id)->with('apply', 'upload your cv in your profile first!');
        } else {
            $jd = $request->job_id;
            $applyJob = Application::create([
                'cv' => Auth::user()->cv,
                'task_id' => $jd,
                'user_id' => Auth::user()->id,
            ]);
            Task::find($request->task_id)->decrement('vacancy', 1);
            if ($applyJob) {
                return redirect('/jobs/single/' . $request->job_id . '')->with('applied', 'you have applied to this Oppertuinity!');
            }
        }
    }

    // public function search(Request $request)
    // {
    //     $job_title = $request->get('job_title');
    //     $job_region = $request->get('job_region');
    //     $job_type = $request->get('job_type');
    //     $searches = Job::select()->where('job_title', 'like', "%job_title%")
    //         ->where('job_region', 'like', "%job_region%")
    //         ->where('job_type', 'like', "%job_type%")
    //         ->get();

    //     return view('jobs.search', compact('searches'));
    // }
    public function search(Request $request)
    {
        $title = $request->get('title');
        $location = $request->get('location');
        $category = $request->get('category');
        $searches = Task::select()->where('title', 'like', "%$title%")
            ->where('location', 'like', "%$location%")
            ->where('', 'like', "%$category%")
            ->get();

        return view('jobs.search', compact('searches'));
    }
}
