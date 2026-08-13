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
        Schema::create('subscription_renewal_requests', function (Blueprint $table) {
            $table->uuid('subscription_renewal_request_id')->primary();
            $table->string('business_id')->nullable();
            $table->string('business_subscription_id')->nullable();
            $table->string('requested_package_id')->nullable();
            $table->enum('requested_billing_cycle', ['monthly', 'yearly'])->default('monthly');
            $table->enum('status', ['pending', 'approved', 'rejected', 'changes_requested', 'cancelled'])->default('pending');
            $table->text('requested_notes')->nullable();
            $table->text('reviewer_notes')->nullable();
            $table->integer('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('resulting_subscription_invoice_id')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('subscription_renewal_requests');
    }
};
