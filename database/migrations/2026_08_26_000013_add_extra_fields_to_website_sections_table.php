<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds an optional small badge/eyebrow (tagline), a secondary call-to-action,
 * and a countdown deadline to the generic section shape so hero/promo/
 * discount banners and the per-section eyebrow labels on the homepage no
 * longer need to be hardcoded in Vue. All nullable - existing rows/types are
 * unaffected until an admin fills them in.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('website_sections', function (Blueprint $table) {
            $table->string('tagline')->nullable()->after('type');
            $table->string('tagline_icon')->nullable()->after('tagline');
            $table->string('secondary_button_text')->nullable()->after('button_link');
            $table->string('secondary_button_link')->nullable()->after('secondary_button_text');
            $table->string('secondary_link_type')->nullable()->after('secondary_button_link');
            $table->string('secondary_link_target_id')->nullable()->after('secondary_link_type');
            $table->timestamp('countdown_end_at')->nullable()->after('secondary_link_target_id');
        });
    }

    public function down(): void
    {
        Schema::table('website_sections', function (Blueprint $table) {
            $table->dropColumn([
                'tagline',
                'tagline_icon',
                'secondary_button_text',
                'secondary_button_link',
                'secondary_link_type',
                'secondary_link_target_id',
                'countdown_end_at',
            ]);
        });
    }
};
