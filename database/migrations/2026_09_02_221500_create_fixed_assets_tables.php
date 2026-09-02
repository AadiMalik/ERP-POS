<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('fixed_asset_categories', function (Blueprint $table) {
            $table->uuid('fixed_asset_category_id')->primary();
            $table->uuid('business_id')->nullable()->index();
            $table->string('code', 50)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('default_useful_life_years')->default(5);
            $table->string('default_depreciation_method', 40)->default('straight_line');
            $table->decimal('default_residual_percent', 8, 2)->default(0);
            $table->string('status', 20)->default('active');

            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('createdby_id')->nullable();
            $table->unsignedBigInteger('updatedby_id')->nullable();
            $table->unsignedBigInteger('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'is_deleted']);
        });

        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->uuid('fixed_asset_id')->primary();
            $table->uuid('business_id')->nullable()->index();
            $table->uuid('branch_id')->nullable()->index();
            $table->uuid('fixed_asset_category_id')->nullable()->index();

            $table->string('asset_code', 80)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('serial_number', 120)->nullable();
            $table->string('location', 255)->nullable();

            $table->date('purchase_date');
            $table->decimal('purchase_cost', 18, 2);
            $table->decimal('residual_value', 18, 2)->default(0);
            $table->decimal('residual_percent', 8, 2)->default(0);
            $table->decimal('min_book_value_percent', 8, 2)->default(0);

            $table->unsignedInteger('useful_life_years')->default(5);
            $table->string('depreciation_method', 40)->default('straight_line');
            $table->string('depreciation_frequency', 20)->default('monthly');
            $table->string('depreciation_adjustment_mode', 20)->default('none');
            $table->decimal('depreciation_adjustment_rate', 8, 2)->default(0);

            $table->uuid('supplier_id')->nullable()->index();
            $table->uuid('purchase_id')->nullable()->index();
            $table->boolean('accounting_from_purchase')->default(false);
            $table->uuid('acquisition_journal_entry_id')->nullable()->index();
            $table->uuid('payment_account_id')->nullable();

            $table->decimal('accumulated_depreciation', 18, 2)->default(0);
            $table->decimal('current_book_value', 18, 2)->default(0);
            $table->decimal('previous_book_value', 18, 2)->default(0);
            $table->decimal('last_depreciation_amount', 18, 2)->default(0);
            $table->date('last_depreciation_date')->nullable();
            $table->date('next_depreciation_date')->nullable()->index();

            $table->string('depreciation_status', 40)->default('active')->index();

            $table->date('disposal_date')->nullable();
            $table->string('disposal_type', 40)->nullable();
            $table->string('disposal_reason', 500)->nullable();
            $table->decimal('sale_price', 18, 2)->nullable();
            $table->uuid('disposal_journal_entry_id')->nullable();
            $table->uuid('disposal_proceeds_account_id')->nullable();

            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('createdby_id')->nullable();
            $table->unsignedBigInteger('updatedby_id')->nullable();
            $table->unsignedBigInteger('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->index(['business_id', 'branch_id', 'is_deleted'], 'fa_biz_branch_del_idx');
            $table->index(['business_id', 'depreciation_status', 'next_depreciation_date'], 'fa_biz_status_nextdep_idx');
        });

        Schema::create('fixed_asset_depreciations', function (Blueprint $table) {
            $table->uuid('fixed_asset_depreciation_id')->primary();
            $table->uuid('fixed_asset_id')->index();
            $table->uuid('business_id')->nullable()->index();
            $table->uuid('branch_id')->nullable();

            $table->string('period_key', 40);
            $table->date('depreciation_date');
            $table->decimal('previous_value', 18, 2);
            $table->decimal('depreciation_amount', 18, 2);
            $table->decimal('new_value', 18, 2);
            $table->decimal('accumulated_depreciation', 18, 2);
            $table->uuid('journal_entry_id')->nullable()->index();
            $table->string('status', 20)->default('posted');
            $table->string('source', 40)->default('scheduler');

            $table->boolean('is_deleted')->default(false);
            $table->unsignedBigInteger('createdby_id')->nullable();
            $table->unsignedBigInteger('deletedby_id')->nullable();
            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->unique(['fixed_asset_id', 'period_key'], 'fa_dep_asset_period_unique');
            $table->index(['business_id', 'depreciation_date'], 'fa_dep_biz_date_idx');
        });

        Schema::create('fixed_asset_transactions', function (Blueprint $table) {
            $table->uuid('fixed_asset_transaction_id')->primary();
            $table->uuid('fixed_asset_id')->index();
            $table->uuid('business_id')->nullable()->index();
            $table->uuid('branch_id')->nullable();

            $table->string('transaction_type', 40);
            $table->date('transaction_date');
            $table->string('description', 1000)->nullable();
            $table->decimal('amount', 18, 2)->nullable();

            $table->uuid('from_branch_id')->nullable();
            $table->uuid('to_branch_id')->nullable();
            $table->string('from_location', 255)->nullable();
            $table->string('to_location', 255)->nullable();

            $table->uuid('journal_entry_id')->nullable()->index();
            $table->string('reference_type', 80)->nullable();
            $table->uuid('reference_id')->nullable();
            $table->json('meta')->nullable();

            $table->unsignedBigInteger('createdby_id')->nullable();
            $table->timestamp('date_created')->nullable();

            $table->index(['fixed_asset_id', 'transaction_type'], 'fa_tx_asset_type_idx');
            $table->index(['business_id', 'transaction_date'], 'fa_tx_biz_date_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('fixed_asset_transactions');
        Schema::dropIfExists('fixed_asset_depreciations');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('fixed_asset_categories');
    }
};
