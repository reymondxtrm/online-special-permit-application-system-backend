<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferenceCode extends Model
{
    protected $fillable = [
        'permit_type',
        'current_reference_code',
    ];
    use HasFactory;
}
