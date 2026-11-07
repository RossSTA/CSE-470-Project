<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentProfile extends Model
{
    protected $fillable = ['university', 'department', 'bio', 'availability'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function skills()
    {
        return $this->hasMany(StudentSkill::class);
    }

    public function portfolioItems()
    {
        return $this->hasMany(PortfolioItem::class);
    }

    public function completeness(): array
    {
        $parts = ['Name' => filled($this->user->name), 'University' => filled($this->university), 'Department' => filled($this->department), 'Bio' => filled($this->bio), 'Availability' => filled($this->availability), 'Skills' => $this->skills()->exists(), 'Portfolio' => $this->portfolioItems()->exists()];
        $weights = ['Name' => 10, 'University' => 15, 'Department' => 10, 'Bio' => 15, 'Availability' => 10, 'Skills' => 20, 'Portfolio' => 20];

        return ['score' => collect($parts)->filter()->keys()->sum(fn ($key) => $weights[$key]), 'missing' => array_keys(array_filter($parts, fn ($v) => ! $v))];
    }
}
