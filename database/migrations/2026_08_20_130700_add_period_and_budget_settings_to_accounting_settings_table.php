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
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->enum('period_closing_mode', ['monthly', 'yearly', 'manual'])->default('manual')->after('enable_accounting');
            $table->enum('budgeting_mode', ['auto', 'manual'])->default('manual')->after('period_closing_mode');
            $table->decimal('budget_growth_percent', 6, 2)->nullable()->default(10.00)->after('budgeting_mode');
            $table->boolean('advanced_accounting_mode')->default(false)->after('budget_growth_percent');
            $table->unsignedTinyInteger('fiscal_year_start_month')->default(1)->after('advanced_accounting_mode');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('accounting_settings', function (Blueprint $table) {
            $table->dropColumn([
                'period_closing_mode',
                'budgeting_mode',
                'budget_growth_percent',
                'advanced_accounting_mode',
                'fiscal_year_start_month',
            ]);
        });
    }
};
