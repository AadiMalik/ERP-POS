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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->uuid('supplier_id')->primary();
            $table->uuid('branch_id')->nullable();
            $table->uuid('business_id')->nullable();
            $table->uuid('account_id')->nullable();
            $table->string('code')->nullable();
            $table->string('name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('contact_person')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('zip_code')->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('ntn')->nullable();
            $table->string('strn')->nullable();
            $table->decimal('credit_limit', 18, 3)->nullable();
            $table->decimal('credit_days', 18, 3)->nullable();
            $table->decimal('balance', 18, 3)->nullable();

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
        Schema::dropIfExists('suppliers');
    }
};
