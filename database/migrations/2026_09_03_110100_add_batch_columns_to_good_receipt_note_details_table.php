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
        Schema::table('good_receipt_note_details', function (Blueprint $table) {
            $table->string('batch_no')->nullable()->after('product_variation_batch_id');
            $table->date('manufacturing_date')->nullable()->after('batch_no');
            $table->date('expiry_date')->nullable()->after('manufacturing_date');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('good_receipt_note_details', function (Blueprint $table) {
            $table->dropColumn(['batch_no', 'manufacturing_date', 'expiry_date']);
        });
    }
};
