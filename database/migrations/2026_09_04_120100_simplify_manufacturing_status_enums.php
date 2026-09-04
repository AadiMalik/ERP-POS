<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Collapses manufacturing_plans.status (draft/confirmed/materials_reserved/
 * in_production/partially_completed/completed/cancelled) down to
 * draft/not_complete/completed/cancelled, and productions.status
 * (draft/confirmed/in_progress/completed/cancelled) down to
 * draft/completed/cancelled - matching ManufacturingPlanStatus/
 * ProductionStatus after the minimal-spec trim. No production/plan data
 * exists yet in any live business at the time of this migration (module
 * just built), so a straight column-type swap is safe.
 */
return new class extends Migration
{
    public function up()
    {
        DB::statement("UPDATE manufacturing_plans SET status = 'not_complete' WHERE status IN ('confirmed', 'materials_reserved', 'in_production', 'partially_completed')");
        DB::statement("ALTER TABLE manufacturing_plans MODIFY COLUMN status ENUM('draft', 'not_complete', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");

        DB::statement("UPDATE productions SET status = 'draft' WHERE status IN ('confirmed', 'in_progress')");
        DB::statement("ALTER TABLE productions MODIFY COLUMN status ENUM('draft', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE manufacturing_plans MODIFY COLUMN status ENUM('draft', 'confirmed', 'materials_reserved', 'in_production', 'partially_completed', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
        DB::statement("ALTER TABLE productions MODIFY COLUMN status ENUM('draft', 'confirmed', 'in_progress', 'completed', 'cancelled') NOT NULL DEFAULT 'draft'");
    }
};
