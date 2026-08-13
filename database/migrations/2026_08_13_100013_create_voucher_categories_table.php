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
        // Scope pivot - an absent row for a voucher means it applies to all categories.
        // Linked to `categories` (Category::category_id) - same leaf-level choice as
        // discount_categories (see that migration's comment for the reasoning).
        Schema::create('voucher_categories', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_id');
            $table->string('category_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('voucher_categories');
    }
};
