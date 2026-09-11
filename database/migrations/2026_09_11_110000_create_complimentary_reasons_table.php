<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('complimentary_reasons')) {
            Schema::create('complimentary_reasons', function (Blueprint $table) {
                $table->uuid('complimentary_reason_id')->primary();
                $table->uuid('business_id')->index();
                $table->string('name');
                $table->string('code', 50)->nullable();
                $table->string('status', 20)->default('active');
                $table->boolean('is_deleted')->default(0);
                $table->uuid('createdby_id')->nullable();
                $table->uuid('updatedby_id')->nullable();
                $table->uuid('deletedby_id')->nullable();
                $table->dateTime('date_created')->nullable();
                $table->dateTime('date_updated')->nullable();
                $table->dateTime('date_deleted')->nullable();

                $table->index(['business_id', 'status', 'is_deleted'], 'complimentary_reasons_business_status_idx');
            });

            return;
        }

        // A previous run created the table then failed on the composite index
        // (utf8mb4 VARCHAR(255) status blew the 1000-byte key limit). Shrink
        // status and add the index so migrate can finish on that leftover table.
        Schema::table('complimentary_reasons', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->change();
        });

        $indexNames = collect(DB::select('SHOW INDEX FROM complimentary_reasons'))
            ->pluck('Key_name')
            ->all();

        if (!in_array('complimentary_reasons_business_status_idx', $indexNames, true)) {
            Schema::table('complimentary_reasons', function (Blueprint $table) {
                $table->index(['business_id', 'status', 'is_deleted'], 'complimentary_reasons_business_status_idx');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('complimentary_reasons');
    }
};
