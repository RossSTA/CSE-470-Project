<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class Job extends Model
{
    protected $fillable = ['title', 'description', 'type', 'budget', 'deadline', 'screening_questions'];

    protected function casts(): array
    {
        return ['budget' => 'decimal:2', 'deadline' => 'date', 'screening_questions' => 'array'];
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'job_skills')->withTimestamps();
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class);
    }

    public function editable(): bool
    {
        return $this->status !== 'closed' && ! $this->contracts()->whereNotNull('started_at')->exists();
    }

    public function transition(string $next): void
    {
        if (! in_array($this->status.'>'.$next, ['draft>published', 'published>closed'])) {
            throw ValidationException::withMessages(['status' => 'This lifecycle transition is not allowed.']);
        }
        if ($next === 'published' && (! $this->deadline->isAfter(today()) || $this->budget <= 0 || ! $this->skills()->exists())) {
            throw ValidationException::withMessages(['deadline' => 'Publishing requires a future deadline, positive budget and skills.']);
        }
        $this->status = $next;
        $this->save();
    }
}
