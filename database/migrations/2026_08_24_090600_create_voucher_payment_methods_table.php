<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        // Scope pivot - an absent row for a voucher means it applies to all payment methods.
        // Checked at order post() time once the actual payment split is known (see
        // OrderService::post()), not at cart-preview time.
        Schema::create('voucher_payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_id');
            $table->string('payment_method_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voucher_payment_methods');
    }
};
