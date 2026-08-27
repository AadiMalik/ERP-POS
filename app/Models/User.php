<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'status',
        'business_id',
        'branch_id',
        'last_login_at',
        'is_deleted',
        'must_change_password',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted'
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
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class,'business_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class,'branch_id');
    }

    public function customerProfiles()
    {
        return $this->hasMany(CustomerProfile::class, 'user_id');
    }

    public function customerProfile($business_id)
    {
        return $this->customerProfiles()->where('business_id', $business_id)->first();
    }

    public function employee()
    {
        return $this->hasOne(Employee::class, 'user_id');
    }

    public function fcmTokens()
    {
        return $this->hasMany(UserFcmToken::class, 'user_id');
    }

    public function activeFcmTokens()
    {
        return $this->fcmTokens()->where('is_active', true);
    }

    public function createdby()
    {
        return $this->belongsTo(User::class,'createdby_id');
    }
    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedby()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }
}
