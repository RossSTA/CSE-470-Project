<?php

namespace App\Services;

use App\Models\Job;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class JobService
{
    public function save(User $client, array $data, ?Job $job = null): Job
    {
        return DB::transaction(function () use ($client, $data, $job) {
            if ($job) {
                $job = Job::whereKey($job->id)->lockForUpdate()->firstOrFail();
                if (! $job->editable()) {
                    throw ValidationException::withMessages(['job' => 'This posting is closed or a contract has started.']);
                }
            } else {
                $job = new Job;
                $job->client_id = $client->id;
            }
            $job->fill(collect($data)->except('skills')->all());
            $job->screening_questions = $data['screening_questions'] ?? [];
            $job->save();
            $job->skills()->sync($data['skills']);

            return $job;
        });
    }

    public function transition(Job $job, string $status): void
    {
        DB::transaction(fn () => Job::whereKey($job->id)->lockForUpdate()->firstOrFail()->transition($status));
    }
}
