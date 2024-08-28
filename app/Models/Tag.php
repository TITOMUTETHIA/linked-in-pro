<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Add this line

class Tag extends Model
{
    use HasFactory;
    public function jobs(): BelongsToMany // Update the return type
    {
        return $this->belongsToMany(Job::class);
    }
}
