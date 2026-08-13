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
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('customer_id')->primary();
            $table->string('business_id')->nullable();
            $table->string('branch_id')->nullable();
            $table->string('account_id')->nullable();

            $table->string('code')->nullable();
            $table->string('name')->nullable();

            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();

            $table->decimal('credit_limit', 18, 3)->nullable();
            $table->integer('credit_days')->nullable();

            // Flags a business's auto-created default walk-in customer, used by the POS module.
            $table->boolean('is_walkin')->default(false);

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
};
