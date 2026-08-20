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
        Schema::create('budgets', function (Blueprint $table) {
            $table->uuid('budget_id')->primary();
            $table->uuid('business_id');
            $table->uuid('fiscal_year_id');

            $table->string('name', 150);
            $table->enum('granularity', ['monthly', 'quarterly', 'yearly']);
            $table->enum('generation_mode', ['auto', 'manual']);
            $table->decimal('growth_percent', 6, 2)->nullable(); // snapshot of the setting used, if auto

            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->unique(['business_id', 'fiscal_year_id'], 'one_budget_per_fiscal_year');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('budgets');
    }
};
