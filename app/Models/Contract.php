<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['started_at' => 'datetime'];
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }
}
