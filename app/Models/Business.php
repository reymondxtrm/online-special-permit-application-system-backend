<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Business extends Model
{
    use HasFactory;

    public function businessStages(): HasMany
    {
        return $this->hasMany(BusinessStage::class);
    }

    public function permitType(): BelongsTo
    {
        return $this->belongsTo(PermitType::class);
    }

    public function genderType(): BelongsTo
    {
        return $this->belongsTo(GenderType::class);
    }
       public function permitReceiver():HasMany
    {
        return $this->hasMany(PermitReceiver::class);
    }
}
