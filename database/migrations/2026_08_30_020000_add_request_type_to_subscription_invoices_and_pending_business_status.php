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
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->enum('request_type', ['new', 'renew'])->nullable()->after('notes');
        });

        DB::statement("ALTER TABLE businesses MODIFY status ENUM('active','suspended','expired','pending','under_review') NOT NULL DEFAULT 'active'");

        Schema::table('intro_contact_inquiries', function (Blueprint $table) {
            $table->string('business_id')->nullable()->after('intro_contact_inquiry_id');
            $table->string('subscription_invoice_id')->nullable()->after('business_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('intro_contact_inquiries', function (Blueprint $table) {
            $table->dropColumn(['business_id', 'subscription_invoice_id']);
        });

        DB::statement("UPDATE businesses SET status = 'active' WHERE status IN ('pending','under_review')");
        DB::statement("ALTER TABLE businesses MODIFY status ENUM('active','suspended','expired') NOT NULL DEFAULT 'active'");

        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropColumn('request_type');
        });
    }
};
