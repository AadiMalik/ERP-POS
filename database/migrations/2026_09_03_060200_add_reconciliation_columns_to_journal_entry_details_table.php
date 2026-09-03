<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('journal_entry_details', function (Blueprint $table) {
            $table->boolean('is_reconciled')->default(false)->after('description');
            $table->uuid('bank_reconciliation_id')->nullable()->after('is_reconciled');
            $table->timestamp('reconciled_at')->nullable()->after('bank_reconciliation_id');
            $table->integer('reconciled_by_id')->nullable()->after('reconciled_at');

            $table->index(['is_reconciled', 'bank_reconciliation_id'], 'jed_reconciled_idx');
        });
    }

    public function down()
    {
        Schema::table('journal_entry_details', function (Blueprint $table) {
            $table->dropIndex('jed_reconciled_idx');
            $table->dropColumn([
                'is_reconciled',
                'bank_reconciliation_id',
                'reconciled_at',
                'reconciled_by_id',
            ]);
        });
    }
};
