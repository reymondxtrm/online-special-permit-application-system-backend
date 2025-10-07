<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpecialPermitType extends Model
{
    use HasFactory;

    public function specialPermitApplications(): HasMany
    {
        return $this->HasMany(SpecialPermitApplication::class);
    }
}
