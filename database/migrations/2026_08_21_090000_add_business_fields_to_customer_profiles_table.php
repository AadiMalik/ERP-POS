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
        Schema::table('customer_profiles', function (Blueprint $table) {
            // Existing address/city/state/country columns serve as the billing
            // address; these are the shipping counterpart.
            $table->string('shipping_address')->nullable()->after('country');
            $table->string('shipping_city')->nullable()->after('shipping_address');
            $table->string('shipping_state')->nullable()->after('shipping_city');
            $table->string('shipping_country')->nullable()->after('shipping_state');

            $table->decimal('opening_balance', 18, 3)->default(0)->after('credit_days');
            $table->enum('opening_balance_type', ['Dr', 'Cr'])->nullable()->after('opening_balance');

            // Free-text term (e.g. "Net 30", "Due on Receipt"), distinct from the
            // numeric credit_days already used for credit-limit enforcement.
            $table->string('payment_terms')->nullable()->after('opening_balance_type');

            $table->text('notes')->nullable()->after('payment_terms');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn([
                'shipping_address',
                'shipping_city',
                'shipping_state',
                'shipping_country',
                'opening_balance',
                'opening_balance_type',
                'payment_terms',
                'notes',
            ]);
        });
    }
};
