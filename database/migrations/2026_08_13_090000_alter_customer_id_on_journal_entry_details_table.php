<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // customer_id was an unused integer column (no call sites anywhere in the
        // codebase). Widen it to a uuid string so it can tag JournalEntryDetail
        // rows against the new Customer master, mirroring how supplier_id works.
        DB::statement('ALTER TABLE journal_entry_details MODIFY customer_id VARCHAR(36) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE journal_entry_details MODIFY customer_id INT NULL');
    }
};
