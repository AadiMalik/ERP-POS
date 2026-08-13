<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Permission records for the centralized Orders domain (order.*) and the
     * POS operational interface (pos.*), seeded as plain rows in the existing
     * `permissions` table like every other module - no dedicated enum class.
     *
     * @return void
     */
    public static function permissions(): array
    {
        return [
            'pos.access' => 'Access POS',
            'pos.register.close' => 'Close Register',
            'pos.register.report.view' => 'View Register Report',

            'order.create' => 'Create Order',
            'order.edit' => 'Edit Order',
            'order.discount.apply' => 'Apply Discount',
            'order.coupon.apply' => 'Apply Coupon',
            'order.price.change' => 'Change Price',
            'order.hold' => 'Hold Order',
            'order.cancel_void' => 'Cancel/Void Order',
            'order.refund.process' => 'Process Refund',
            'order.payment.credit' => 'Take Credit Payment',
            'order.customer.change' => 'Change Customer',
            'order.reopen' => 'Reopen Order',
        ];
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::permissions() as $name => $label) {
            $exists = DB::table('permissions')
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->exists();

            if (!$exists) {
                DB::table('permissions')->insert([
                    'name' => $name,
                    'guard_name' => 'web',
                    'is_system_only' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('permissions')
            ->whereIn('name', array_keys(self::permissions()))
            ->where('guard_name', 'web')
            ->delete();
    }
};
