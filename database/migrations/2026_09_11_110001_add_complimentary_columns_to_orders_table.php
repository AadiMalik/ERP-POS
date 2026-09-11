<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('complimentary_status', 20)->default('none')->after('notes');
            $table->uuid('complimentary_reason_id')->nullable()->after('complimentary_status');
            $table->text('complimentary_notes')->nullable()->after('complimentary_reason_id');
            $table->uuid('complimentary_by_id')->nullable()->after('complimentary_notes');
            $table->dateTime('complimentary_at')->nullable()->after('complimentary_by_id');
            $table->decimal('complimentary_retail_value', 18, 3)->default(0)->after('complimentary_at');
            $table->decimal('complimentary_cost', 18, 3)->default(0)->after('complimentary_retail_value');

            $table->index(['business_id', 'complimentary_status', 'sale_date'], 'orders_complimentary_status_date_idx');
            $table->index('complimentary_reason_id', 'orders_complimentary_reason_idx');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_complimentary_status_date_idx');
            $table->dropIndex('orders_complimentary_reason_idx');
            $table->dropColumn([
                'complimentary_status',
                'complimentary_reason_id',
                'complimentary_notes',
                'complimentary_by_id',
                'complimentary_at',
                'complimentary_retail_value',
                'complimentary_cost',
            ]);
        });
    }
};
