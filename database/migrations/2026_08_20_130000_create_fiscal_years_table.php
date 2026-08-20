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
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->uuid('fiscal_year_id')->primary();
            $table->uuid('business_id');

            $table->string('name', 100);
            $table->date('start_date');
            $table->date('end_date');

            $table->enum('status', ['upcoming', 'open', 'closed'])->default('upcoming');
            $table->boolean('is_current')->default(false); // exactly one true per business

            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->unique(['business_id', 'start_date', 'end_date'], 'fiscal_year_range_unique');
            $table->index(['business_id', 'status'], 'fiscal_year_status_idx');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('fiscal_years');
    }
};
