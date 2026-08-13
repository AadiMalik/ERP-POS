<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscription_settings', function (Blueprint $table) {
            $table->uuid('subscription_setting_id')->primary();
            $table->integer('default_grace_period_days')->default(7);
            $table->json('reminder_thresholds_days')->nullable();
            $table->boolean('restrict_access_in_grace_period')->default(true);
            $table->json('restricted_route_names')->nullable();
            $table->string('invoice_prefix')->default('SUB-INV');

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });

        DB::table('subscription_settings')->insert([
            'subscription_setting_id' => (string) Str::uuid(),
            'default_grace_period_days' => 7,
            'reminder_thresholds_days' => json_encode([30, 15, 7, 3, 1]),
            'restrict_access_in_grace_period' => true,
            'restricted_route_names' => json_encode([
                'my-subscription.index',
                'my-subscription.renewal-requests',
                'my-subscription.renewal-requests.store',
                'my-subscription.invoice-pdf',
                'my-subscription.payments.store',
                'logout',
            ]),
            'invoice_prefix' => 'SUB-INV',
            'is_deleted' => 0,
            'date_created' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_settings');
    }
};
