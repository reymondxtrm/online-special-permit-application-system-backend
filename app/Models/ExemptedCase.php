<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExemptedCase extends Model
{
    use HasFactory;

    public function permitApplicationExemptions(): HasMany
    {
        return $this->hasMany(PermitApplicationExemption::class);
    }
}
