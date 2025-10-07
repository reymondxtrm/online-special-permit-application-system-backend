<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PermitType extends Model
{
    use HasFactory;
    

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }
}
