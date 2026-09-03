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
        Schema::table('transfer_note_details', function (Blueprint $table) {
            $table->decimal('received_quantity', 18, 3)->default(0.000)->after('transfer_quantity');
        });

        Schema::table('transfer_notes', function (Blueprint $table) {
            $table->uuid('destination_branch_id')->nullable()->after('destination_warehouse_id');
            $table->integer('sentby_id')->nullable()->after('createdby_id');
            $table->timestamp('date_sent')->nullable()->after('date_created');
            $table->integer('receivedby_id')->nullable()->after('sentby_id');
            $table->timestamp('date_received')->nullable()->after('date_sent');
        });

        // Backfill destination_branch_id from the destination warehouse's own branch.
        DB::statement('
            UPDATE transfer_notes tn
            INNER JOIN warehouses w ON w.warehouse_id = tn.destination_warehouse_id
            SET tn.destination_branch_id = w.branch_id
        ');

        // Old "Approved" already moved stock out AND in under the previous one-shot
        // logic, so it maps to the new "Received" (fully completed), not "In Transit".
        DB::statement("
            UPDATE transfer_notes
            SET status = 'received', date_received = date_updated
            WHERE status = 'approved'
        ");

        DB::statement('
            UPDATE transfer_note_details td
            INNER JOIN transfer_notes tn ON tn.transfer_note_id = td.transfer_note_id
            SET td.received_quantity = td.transfer_quantity
            WHERE tn.status = \'received\'
        ');

        DB::statement("UPDATE transfer_notes SET status = 'draft' WHERE status = 'pending'");

        DB::statement("ALTER TABLE transfer_notes MODIFY status ENUM('draft','in_transit','received','cancelled') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("UPDATE transfer_notes SET status = 'pending' WHERE status = 'draft'");
        DB::statement("UPDATE transfer_notes SET status = 'approved' WHERE status IN ('in_transit', 'received')");

        DB::statement("ALTER TABLE transfer_notes MODIFY status ENUM('pending','approved','cancelled') NOT NULL DEFAULT 'pending'");

        Schema::table('transfer_notes', function (Blueprint $table) {
            $table->dropColumn(['destination_branch_id', 'sentby_id', 'date_sent', 'receivedby_id', 'date_received']);
        });

        Schema::table('transfer_note_details', function (Blueprint $table) {
            $table->dropColumn('received_quantity');
        });
    }
};
