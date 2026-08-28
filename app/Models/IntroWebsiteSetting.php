<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntroWebsiteSetting extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'intro_website_settings';
    protected $primaryKey = 'intro_website_setting_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
        'label',
        'updatedby_id',
        'date_updated'
    ];
}
