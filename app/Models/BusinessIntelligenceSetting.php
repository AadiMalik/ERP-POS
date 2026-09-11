<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessIntelligenceSetting extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'business_id',
        'discount_change_threshold_percent',
        'voucher_change_threshold_percent',
        'complimentary_sales_percent_threshold',
        'high_discount_percent_threshold',
        'high_return_rate_percent',
        'high_cancellation_rate_percent',
        'dead_stock_days',
        'slow_moving_days',
        'delayed_order_hours',
        'offline_sync_stale_hours',
        'high_waste_percent_of_stock',
        'attendance_repeat_late_count',
        'low_margin_percent',
        'excellent_sales_growth_percent',
        'createdby_id',
        'updatedby_id',
        'date_created',
        'date_updated',
    ];

    protected $casts = [
        'discount_change_threshold_percent' => 'float',
        'voucher_change_threshold_percent' => 'float',
        'complimentary_sales_percent_threshold' => 'float',
        'high_discount_percent_threshold' => 'float',
        'high_return_rate_percent' => 'float',
        'high_cancellation_rate_percent' => 'float',
        'dead_stock_days' => 'integer',
        'slow_moving_days' => 'integer',
        'delayed_order_hours' => 'integer',
        'offline_sync_stale_hours' => 'integer',
        'high_waste_percent_of_stock' => 'float',
        'attendance_repeat_late_count' => 'integer',
        'low_margin_percent' => 'float',
        'excellent_sales_growth_percent' => 'float',
    ];

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id');
    }
}
