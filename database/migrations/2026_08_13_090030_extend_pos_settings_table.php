<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->enum('register_mode', ['manual', 'automatic'])->default('manual')->after('business_id');
            $table->boolean('enable_hold_order')->default(true)->after('show_product_image');
            $table->enum('discount_level', ['line', 'order', 'both'])->default('both')->after('enable_discount');
            $table->boolean('allow_backdated_sale')->default(false)->after('discount_level');
            $table->integer('backdated_sale_max_days')->nullable()->after('allow_backdated_sale');
            $table->enum('daily_order_id_reset', ['daily', 'never'])->default('daily')->after('backdated_sale_max_days');
            $table->string('default_order_type_id')->nullable()->after('default_payment_method_id');
            $table->string('default_order_source_id')->nullable()->after('default_order_type_id');
            $table->integer('return_window_days')->nullable()->after('daily_order_id_reset');
            $table->boolean('require_return_reason')->default(true)->after('return_window_days');
            $table->boolean('allow_partial_return')->default(true)->after('require_return_reason');
        });

        // default_customer_id / default_payment_method_id were unused integer columns.
        // Widen them to uuid strings since Customer/PosPaymentMethod (built later in
        // this same project) both use uuid PKs.
        DB::statement('ALTER TABLE pos_settings MODIFY default_customer_id VARCHAR(36) NULL');
        DB::statement('ALTER TABLE pos_settings MODIFY default_payment_method_id VARCHAR(36) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pos_settings', function (Blueprint $table) {
            $table->dropColumn([
                'register_mode',
                'enable_hold_order',
                'discount_level',
                'allow_backdated_sale',
                'backdated_sale_max_days',
                'daily_order_id_reset',
                'default_order_type_id',
                'default_order_source_id',
                'return_window_days',
                'require_return_reason',
                'allow_partial_return',
            ]);
        });

        DB::statement('ALTER TABLE pos_settings MODIFY default_customer_id INT NULL');
        DB::statement('ALTER TABLE pos_settings MODIFY default_payment_method_id INT NULL');
    }
};
