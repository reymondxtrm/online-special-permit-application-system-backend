<?php

namespace App\Models;

use Database\Seeders\PrimaryIdType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PermitReceiver extends Model
{
    use HasFactory;
    protected $fillable = [
        "receiver_name",
        'receiver_signature',
        'receiver_picture',
        'receiver_relationship_to_owner',
        'receiver_phone_no',
        'receiver_email',
        'receiver_id_type',
        'receiver_id_no'
    ];
    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
    public function idType(): HasOne
    {
        return $this->hasOne(IdType::class);
    }
    public function idTypeOther(): HasOne
    {
        return $this->hasOne(IdTypeOther::class);
    }
}
