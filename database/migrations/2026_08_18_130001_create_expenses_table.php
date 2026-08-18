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
        Schema::create('expenses', function (Blueprint $table) {
            $table->uuid('expense_id')->primary();
            $table->uuid('business_id');
            $table->uuid('branch_id')->nullable();
            $table->uuid('expense_category_id');

            // Set only for expenses recorded against an active POS register
            // session (source = 'pos', or an admin manually attaching an
            // existing session on the Expense Detail CRUD).
            $table->uuid('pos_register_session_id')->nullable();

            // The Order Taker / cashier / staff member this expense is
            // attributed to. Nullable for Admin Expenses that aren't tied to
            // any specific user.
            $table->integer('user_id')->nullable();

            $table->string('expense_no')->nullable();
            $table->timestamp('expense_date')->nullable();
            $table->decimal('amount', 18, 3)->default(0.000);

            $table->enum('payment_method', ['cash', 'bank_transfer', 'cheque', 'online'])->default('cash');
            $table->uuid('payment_account_id')->nullable();

            // Snapshot of the Chart of Accounts (Expenses type) account this
            // expense posts to on the debit leg, resolved from the category
            // (or the business's default expense account) at save time.
            $table->uuid('expense_account_id')->nullable();

            $table->string('reference_no')->nullable();
            $table->text('description')->nullable();
            $table->string('attachment')->nullable();

            // 'pos' = entered by an OT from the POS screen against their
            // active session. 'admin' = entered from the Admin panel
            // (Expense Detail CRUD, with or without a linked session, or the
            // dedicated Admin Expense CRUD).
            $table->enum('source', ['pos', 'admin'])->default('admin');

            $table->enum('status', ['pending', 'posted', 'cancelled'])->default('pending');
            $table->integer('postedby_id')->nullable();
            $table->timestamp('date_posted')->nullable();

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
        Schema::dropIfExists('expenses');
    }
};
