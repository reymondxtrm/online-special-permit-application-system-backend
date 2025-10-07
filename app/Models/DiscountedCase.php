<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DiscountedCase extends Model
{
    use HasFactory;

    public function permitApplicationDiscounts(): HasMany
    {
        return $this->hasMany(PermitApplicationDiscount::class);
    }
}
