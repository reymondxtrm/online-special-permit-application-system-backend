<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SpecialPermitApplication extends Model
{
    use HasFactory;

    protected $fillable = [
        'special_permit_type_id',
        'application_purpose_id',
        'surname',
        'first_name',
        'middle_initial',
        'suffix',
        'sex',
        'email',
        'contact_no',
        'date',
        'province',
        'city',
        'barangay',
        'additional_address',
        'or_no',
        'paid_amount',
    ];

    public function uploadedFile(): HasOne
    {
        return $this->hasOne(UploadedFile::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function applicationPurpose(): BelongsTo
    {
        return $this->belongsTo(ApplicationPurpose::class);
    }

    public function specialPermitType(): BelongsTo
    {
        return $this->belongsTo(SpecialPermitType::class);
    }

    public function permitApplicationDiscount(): HasOne
    {
        return $this->hasOne(PermitApplicationDiscount::class);
    }

    public function permitApplicationExemption(): HasOne
    {
        return $this->hasOne(PermitApplicationExemption::class);
    }

    public function orderOfPayment(): HasOne
    {
        return $this->hasOne(OrderOfPayment::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(StatusHistory::class);
    }
}
