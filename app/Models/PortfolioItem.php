<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioItem extends Model
{
    protected $fillable = ['title', 'description', 'url'];

    public function profile()
    {
        return $this->belongsTo(StudentProfile::class, 'student_profile_id');
    }
}
