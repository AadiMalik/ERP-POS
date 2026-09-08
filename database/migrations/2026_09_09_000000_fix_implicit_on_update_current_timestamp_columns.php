<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * This server has explicit_defaults_for_timestamp OFF (a MySQL/MariaDB legacy
 * default), so the first non-nullable TIMESTAMP column in a table silently
 * gets `DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP` attached by
 * the server itself - regardless of what the Laravel migration that created
 * the column actually asked for (none of these four asked for it). The
 * practical effect: every time the row is updated for ANY reason, MySQL
 * silently overwrites the column with "now" - evaluated in the MySQL
 * server's session/system timezone, not this application's UTC contract -
 * corrupting a value the application already explicitly manages.
 *
 * Found via a live bug report: orders.order_date (set correctly as UTC on
 * insert) was getting silently rewritten to the MySQL server's local
 * wall-clock time on the very next update to that order row. The same
 * `ON UPDATE current_timestamp()` was found on otps.expires_at,
 * payment_gateway_webhook_logs.received_at, and
 * pos_register_sessions.opening_datetime - all explicitly set via now() in
 * PHP and never meant to be auto-managed by the database.
 *
 * Fix: give each column an explicit `DEFAULT CURRENT_TIMESTAMP` (satisfies
 * MySQL's NOT NULL requirement; never actually used since the app always
 * supplies the value) with NO `ON UPDATE` clause - stating any explicit
 * default suppresses MySQL's implicit auto-both-clauses behavior entirely.
 * Does not touch stored data - only the column's own default/on-update
 * metadata.
 */
class FixImplicitOnUpdateCurrentTimestampColumns extends Migration
{
    protected array $columns = [
        'orders' => 'order_date',
        'otps' => 'expires_at',
        'payment_gateway_webhook_logs' => 'received_at',
        'pos_register_sessions' => 'opening_datetime',
    ];

    public function up()
    {
        foreach ($this->columns as $table => $column) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }
    }

    public function down()
    {
        foreach ($this->columns as $table => $column) {
            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
        }
    }
}
