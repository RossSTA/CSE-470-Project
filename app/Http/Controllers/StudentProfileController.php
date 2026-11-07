<?php

namespace App\Http\Controllers;

use App\Http\Requests\PortfolioRequest;
use App\Http\Requests\ProfileRequest;
use App\Http\Requests\StudentSkillRequest;
use App\Models\PortfolioItem;
use App\Models\Skill;
use App\Models\StudentProfile;
use App\Models\StudentSkill;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class StudentProfileController extends Controller
{
    public function show(StudentProfile $profile)
    {
        return view('profile.show', compact('profile'));
    }

    public function edit(StudentProfile $profile)
    {
        Gate::authorize('update', $profile);

        return view('profile.edit', ['profile' => $profile, 'skills' => Skill::orderBy('name')->get()]);
    }

    public function update(ProfileRequest $r, StudentProfile $profile)
    {
        Gate::authorize('update', $profile);
        DB::transaction(function () use ($r, $profile) {
            $profile->update($r->safe()->except('name'));
            $profile->user->update(['name' => $r->validated('name')]);
        });

        return back()->with('success', 'Profile updated.');
    }

    public function skill(StudentSkillRequest $r, StudentProfile $profile, ?StudentSkill $studentSkill = null)
    {
        Gate::authorize('update', $profile);
        if ($studentSkill) {
            abort_unless($studentSkill->student_profile_id === $profile->id, 404);
            $studentSkill->update($r->validated());
        } else {
            $profile->skills()->create($r->validated());
        }

return back()->with('success', 'Skill saved.');
    }

    public function removeSkill(StudentProfile $profile, StudentSkill $studentSkill)
    {
        Gate::authorize('update', $profile);
        abort_unless($studentSkill->student_profile_id === $profile->id, 404);
        $studentSkill->delete();

        return back()->with('success', 'Skill removed.');
    }

    public function project(PortfolioRequest $r, StudentProfile $profile)
    {
        Gate::authorize('update', $profile);
        $profile->portfolioItems()->create($r->validated());

        return back()->with('success', 'Project added.');
    }

    public function editProject(PortfolioItem $portfolioItem)
    {
        Gate::authorize('update', $portfolioItem);

        return view('profile.project', compact('portfolioItem'));
    }

    public function updateProject(PortfolioRequest $r, PortfolioItem $portfolioItem)
    {
        Gate::authorize('update', $portfolioItem);
        $portfolioItem->update($r->validated());

        return redirect()->route('profile.edit', $portfolioItem->profile)->with('success', 'Project updated.');
    }

    public function deleteProject(PortfolioItem $portfolioItem)
    {
        Gate::authorize('update', $portfolioItem);
        $portfolioItem->delete();

        return back()->with('success','Project removed.');
    }
}
