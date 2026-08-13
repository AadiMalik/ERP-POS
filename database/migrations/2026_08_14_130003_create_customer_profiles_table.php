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
        // Business-scoped commercial profile for a `users` row with the "User"
        // (customer) role. Identity (name/email/phone/password) stays on `users`
        // so the same login works across every business - this table only holds
        // the per-business relationship (credit terms, AR account, loyalty, ...).
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->uuid('customer_profile_id')->primary();
            $table->unsignedBigInteger('user_id');
            $table->string('business_id');
            $table->string('branch_id')->nullable();
            $table->string('account_id')->nullable();

            // Traceability back to the pre-refactor customers.customer_id row this
            // profile was backfilled from, if any.
            $table->uuid('legacy_customer_id')->nullable();

            $table->string('code')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();

            $table->decimal('credit_limit', 18, 3)->nullable();
            $table->integer('credit_days')->nullable();

            // Flags a business's auto-created default walk-in customer, used by the POS module.
            $table->boolean('is_walkin')->default(false);

            $table->decimal('loyalty_points', 18, 3)->default(0);

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->unique(['business_id', 'user_id']);
            $table->unique(['business_id', 'code']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_profiles');
    }
};
