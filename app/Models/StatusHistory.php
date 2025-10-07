<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StatusHistory extends Model
{
    use HasFactory;

    public function specialPermitApplication(): BelongsTo
    {
        return $this->belongsTo(SpecialPermitApplication::class);
    }
}
