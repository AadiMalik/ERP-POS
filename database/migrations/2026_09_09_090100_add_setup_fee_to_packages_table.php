<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One-time onboarding/setup fee, stored separately from recurring
     * subscription pricing (`price`/`price_yearly`) - never read into usage
     * or price calculations, purely a separate billable line item.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->decimal('setup_fee', 12, 2)->default(0)->after('price_yearly');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn('setup_fee');
        });
    }
};
