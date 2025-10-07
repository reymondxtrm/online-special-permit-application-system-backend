<?php

namespace App\Models;

use App\Models\PermitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class GenderType extends Model
{
    use HasFactory;

    public function gender(): BelongsTo
    {
        return $this->belongsTo(Gender::class);
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    public function businesses(): HasMany
    {
        return $this->hasMany(Business::class);
    }
}
