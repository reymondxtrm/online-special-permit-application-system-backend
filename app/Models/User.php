<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ApprovalStageNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use LdapRecord\Laravel\Auth\LdapAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use LdapRecord\Laravel\Auth\AuthenticatesWithLdap;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use App\Notifications\CustomVerifyEmail;
use App\Notifications\DisapprovalNotification;
use App\Notifications\IssuancePermitNotification;
use Illuminate\Auth\Passwords\CanResetPassword;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\SuccessRegistrationNotification;
use Illuminate\Auth\Notifications\ResetPassword;

class User extends Authenticatable implements LdapAuthenticatable, MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, AuthenticatesWithLdap, Notifiable, CanResetPassword;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    
    protected $fillable = [
        'email',
        'password',
        'fname',
        'mname',
        'sex',
        'username',
        'lname',
        'user_type',

    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */


    // public function sendPasswordResetNotification($token)
    // {
    //     $this->notify(new ResetPasswordNotification($token));
    // }
    public function sendEmailVerificationNotification()
    {
        $this->notify(new CustomVerifyEmail);
    }
    public function sendSuccessRegistrationNotification()
    {
        $this->notify(new SuccessRegistrationNotification());
    }
    public function sendNewPassword($password)
    {
        $this->notify(new ResetPasswordNotification($password));
    }
    public function sendPermitNotification($stage, $type, $reference_code)
    {
        $this->notify(new ApprovalStageNotification($stage, $type, $reference_code));
    }
    public function sendDisapprovalNotification($reason, $permit_type)
    {
        $this->notify(new DisapprovalNotification($reason, $permit_type));
    }
    public function sendIssuancePermitNotifcation($permit_type)
    {
        $this->notify(new IssuancePermitNotification($permit_type));
    }
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function userRoles(): HasMany
    {
        return $this->hasMany(UserRole::class);
    }
    public function businessStages(): HasMany
    {
        return $this->hasMany(BusinessStage::class);
    }
    public function specialPermitApplications(): HasMany
    {
        return $this->hasMany(SpecialPermitApplication::class);
    }
    public function userDetails(): HasOne
    {
        return $this->hasOne(UserDetail::class);
    }
    public function userPhoneNumbers(): HasMany
    {
        return $this->hasMany(UserPhoneNumber::class);
    }
    public function userAddresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }
    public function userOccupationDetails(): HasOne
    {
        return $this->hasOne(UserOccupationDetail::class);
    }
}
