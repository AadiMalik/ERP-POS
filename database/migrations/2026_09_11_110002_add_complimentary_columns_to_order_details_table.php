<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->boolean('is_complimentary')->default(0)->after('notes');
            $table->uuid('complimentary_reason_id')->nullable()->after('is_complimentary');
            $table->text('complimentary_notes')->nullable()->after('complimentary_reason_id');
            $table->decimal('complimentary_value', 18, 3)->default(0)->after('complimentary_notes');
            $table->uuid('complimentary_by_id')->nullable()->after('complimentary_value');
            $table->dateTime('complimentary_at')->nullable()->after('complimentary_by_id');

            $table->index(['order_id', 'is_complimentary'], 'order_details_complimentary_idx');
            $table->index('complimentary_reason_id', 'order_details_complimentary_reason_idx');
        });
    }

    public function down()
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropIndex('order_details_complimentary_idx');
            $table->dropIndex('order_details_complimentary_reason_idx');
            $table->dropColumn([
                'is_complimentary',
                'complimentary_reason_id',
                'complimentary_notes',
                'complimentary_value',
                'complimentary_by_id',
                'complimentary_at',
            ]);
        });
    }
};
