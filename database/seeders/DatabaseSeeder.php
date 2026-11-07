<?php

namespace Database\Seeders;

use App\Models\Job;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['PHP', 'Laravel', 'JavaScript', 'UI Design', 'Writing', 'Python', 'Data Analysis'] as $name) {
            Skill::firstOrCreate(['name' => $name]);
        }
        if (! app()->environment(['local', 'testing'])) {
            return;
        }
        foreach (['student', 'client', 'admin'] as $role) {
            $u = User::firstOrNew(['email' => $role.'@campusgig.test']);
            if (! $u->exists) {
                $u->fill(['name' => ucfirst($role).' Demo', 'password' => 'CampusGig!2026']);
                $u->role = $role;
                $u->save();
            }if ($role === 'student') {
                $p = $u->profile()->firstOrCreate([], ['university' => 'BRAC University', 'department' => 'CSE', 'bio' => 'I build useful websites.', 'availability' => 'part-time']);
                $p->skills()->firstOrCreate(['skill_id' => Skill::where('name', 'Laravel')->firstOrFail()->id], ['proficiency' => 'intermediate']);
                $p->portfolioItems()->firstOrCreate(['title' => 'Campus portfolio'], ['description' => 'A student project portfolio.', 'url' => 'https://example.com']);
            }
        }
        $client = User::where('email', 'client@campusgig.test')->firstOrFail();
        foreach (['gig' => 'Build a club website', 'internship' => 'Web development internship'] as $type => $title) {
            $job = Job::firstOrNew(['client_id' => $client->id, 'title' => $title]);
            if (! $job->exists) {
                $job->fill(['description' => 'Help our campus team create accessible web experiences. Share a project you are proud of.', 'type' => $type, 'budget' => '12000.00', 'deadline' => today()->addDays(30), 'screening_questions' => ['Tell us about a relevant project.']]);
                $job->status = 'published';
                $job->save();
                $job->skills()->sync([Skill::where('name', 'Laravel')->firstOrFail()->id]);
            }
        }
    }
}
