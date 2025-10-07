<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdType extends Model
{

    use HasFactory;

    protected $tables = 'id_Types';

    public function permitReceiver(): BelongsTo
    {
        return $this->belongsTo(PermitReceiver::class);
    }
}
