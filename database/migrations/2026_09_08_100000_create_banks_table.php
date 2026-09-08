<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Branch-scoped bank accounts a business can collect card/bank payments
     * into - a Bank is a normal admin master (like Warehouse before its
     * branch_warehouses upgrade) with a nullable branch_id: set means the
     * bank belongs to one branch, null means it's shared across every
     * branch of the business. Each Bank links to a Chart-of-Accounts
     * account so POS/accounting posting always resolves a real ledger
     * account, never a hardcoded one.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->uuid('bank_id')->primary();
            $table->uuid('business_id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('account_id')->nullable();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('account_number')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index('business_id');
            $table->index('branch_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('banks');
    }
};
