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
        Schema::table('print_settings', function (Blueprint $table) {
            $table->string('document_type')->default('default')->after('business_id');
        });

        // Backfill any pre-existing rows explicitly (column DEFAULT only guarantees
        // future inserts) so every existing business's print config keeps resolving
        // under the 'default' document type exactly as it did before this column existed.
        DB::table('print_settings')->whereNull('document_type')->update(['document_type' => 'default']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('print_settings', function (Blueprint $table) {
            $table->dropColumn('document_type');
        });
    }
};
