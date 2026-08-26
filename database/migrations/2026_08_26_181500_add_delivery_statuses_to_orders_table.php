<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Widen orders.status to support delivery fulfilment steps used by
     * OrderService::changeStatus() (shipped / out_for_delivery / delivered).
     * Existing rows keep their current value and meaning.
     */
    public function up()
    {
        DB::statement("ALTER TABLE orders MODIFY status ENUM('draft','hold','posted','cancelled','void','returned','shipped','out_for_delivery','delivered') NOT NULL DEFAULT 'draft'");
    }

    /**
     * Only safe if no order currently uses a delivery fulfilment status.
     */
    public function down()
    {
        DB::statement("ALTER TABLE orders MODIFY status ENUM('draft','hold','posted','cancelled','void','returned') NOT NULL DEFAULT 'draft'");
    }
};
