<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Business extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $primaryKey = 'business_id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = [
        'business_id',
        'package_id',
        'owner_name',
        'owner_email',
        'owner_phone',
        'code',
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'logo',
        'status',
        'description',
        'subscription_start',
        'subscription_end',

        'is_deleted',
        'createdby_id',
        'updatedby_id',
        'deletedby_id',
        'date_created',
        'date_updated',
        'date_deleted',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function createdby()
    {
        return $this->belongsTo(User::class, 'createdby_id');
    }
    public function updatedby()
    {
        return $this->belongsTo(User::class, 'updatedby_id');
    }

    public function deletedby()
    {
        return $this->belongsTo(User::class, 'deletedby_id');
    }

    public function businessSetting()
    {
        return $this->hasOne(BusinessSetting::class, 'business_id', 'business_id');
    }

    public function accountingSetting()
    {
        return $this->hasOne(AccountingSetting::class, 'business_id', 'business_id');
    }

    public function customerSetting()
    {
        return $this->hasOne(CustomerSetting::class, 'business_id', 'business_id');
    }

    public function supplierSetting()
    {
        return $this->hasOne(SupplierSetting::class, 'business_id', 'business_id');
    }

    public function inventorySetting()
    {
        return $this->hasOne(InventorySetting::class, 'business_id', 'business_id');
    }

    public function emailSetting()
    {
        return $this->hasOne(EmailSetting::class, 'business_id', 'business_id');
    }

    public function smsSetting()
    {
        return $this->hasOne(SmsSetting::class, 'business_id', 'business_id');
    }

    public function fbrSetting()
    {
        return $this->hasOne(FbrSetting::class, 'business_id', 'business_id');
    }

    public function whatsappSetting()
    {
        return $this->hasOne(WhatsappSetting::class, 'business_id', 'business_id');
    }

}
