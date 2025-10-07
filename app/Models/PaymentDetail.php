<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentDetail extends Model
{
    use HasFactory;

    public function orderOfPayment(): BelongsTo
    {
        return $this->belongsTo(OrderOfPayment::class);
    }
}
