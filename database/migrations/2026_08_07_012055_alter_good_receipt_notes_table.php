<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::table('good_receipt_notes', function (Blueprint $table) {
            $table->uuid('supplier_id')->nullable()->after('business_id');
            $table->decimal('total', 18, 3)->nullable()->default(0)->after('description');
        });

        DB::statement("ALTER TABLE good_receipt_notes MODIFY status ENUM('pending','approved','cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("ALTER TABLE good_receipt_notes MODIFY status ENUM('draft','received') NOT NULL DEFAULT 'received'");

        Schema::table('good_receipt_notes', function (Blueprint $table) {
            $table->dropColumn(['supplier_id', 'total']);
        });
    }
};
