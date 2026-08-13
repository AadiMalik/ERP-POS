<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Superseded by business_settings.overall_tax_rate / card_tax_rate - tax is
     * now resolved automatically per order from Business Settings rather than
     * picked per line from a named-rate master.
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('tax_rates');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->uuid('tax_rate_id')->primary();
            $table->string('business_id');
            $table->string('name');
            $table->decimal('rate', 5, 2);
            $table->string('account_id')->nullable();
            $table->boolean('is_default')->default(false);
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
};
