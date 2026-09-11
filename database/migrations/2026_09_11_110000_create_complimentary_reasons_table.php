<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('complimentary_reasons', function (Blueprint $table) {
            $table->uuid('complimentary_reason_id')->primary();
            $table->uuid('business_id')->index();
            $table->string('name');
            $table->string('code', 50)->nullable();
            $table->string('status')->default('active');
            $table->boolean('is_deleted')->default(0);
            $table->uuid('createdby_id')->nullable();
            $table->uuid('updatedby_id')->nullable();
            $table->uuid('deletedby_id')->nullable();
            $table->dateTime('date_created')->nullable();
            $table->dateTime('date_updated')->nullable();
            $table->dateTime('date_deleted')->nullable();

            $table->index(['business_id', 'status', 'is_deleted'], 'complimentary_reasons_business_status_idx');
        });
    }

    public function down()
    {
        Schema::dropIfExists('complimentary_reasons');
    }
};
