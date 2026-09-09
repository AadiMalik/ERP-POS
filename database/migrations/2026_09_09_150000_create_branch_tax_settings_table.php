<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Branch-level tax configuration - unlike thermal_print_settings' two-tier
 * business-default + branch-override model, tax is mandatory per branch (no
 * business-wide fallback row), so branch_id is required and unique here. See
 * TaxSettingResolverService for how this is resolved at order-save time.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('branch_tax_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id');
            $table->string('branch_id');
            $table->decimal('overall_tax_rate', 5, 2)->default(0);
            $table->decimal('card_tax_rate', 5, 2)->default(0);
            $table->enum('tax_type', ['inclusive', 'exclusive'])->default('exclusive');

            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();

            $table->unique('branch_id');
            $table->index('business_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('branch_tax_settings');
    }
};
