<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name', 'email', 'password'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed', 'email_verified_at' => 'datetime'];
    }

    public function profile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function verifications()
    {
        return $this->hasMany(VerificationRequest::class);
    }

    public function isVerified(): bool
    {
        return $this->role === 'student' && $this->verifications()->where('status', 'approved')->exists();
    }
}
