<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer-facing bank transfer details for the website checkout.
 * Stored on website_theme_settings (same place as other storefront config).
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('website_theme_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('website_theme_settings', 'bank_name')) {
                $table->string('bank_name')->nullable()->after('free_delivery_min_amount');
            }
            if (!Schema::hasColumn('website_theme_settings', 'bank_account_title')) {
                $table->string('bank_account_title')->nullable()->after('bank_name');
            }
            if (!Schema::hasColumn('website_theme_settings', 'bank_account_number')) {
                $table->string('bank_account_number')->nullable()->after('bank_account_title');
            }
            if (!Schema::hasColumn('website_theme_settings', 'bank_iban')) {
                $table->string('bank_iban')->nullable()->after('bank_account_number');
            }
            if (!Schema::hasColumn('website_theme_settings', 'bank_branch')) {
                $table->string('bank_branch')->nullable()->after('bank_iban');
            }
            if (!Schema::hasColumn('website_theme_settings', 'bank_swift_code')) {
                $table->string('bank_swift_code')->nullable()->after('bank_branch');
            }
            if (!Schema::hasColumn('website_theme_settings', 'bank_instructions')) {
                $table->text('bank_instructions')->nullable()->after('bank_swift_code');
            }
        });
    }

    public function down()
    {
        Schema::table('website_theme_settings', function (Blueprint $table) {
            $cols = [
                'bank_name',
                'bank_account_title',
                'bank_account_number',
                'bank_iban',
                'bank_branch',
                'bank_swift_code',
                'bank_instructions',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('website_theme_settings', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
