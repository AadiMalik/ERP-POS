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
        // Scope pivot - an absent row for a discount means it applies to all categories.
        // Linked to `categories` (Category::category_id) - the required, always-present
        // category level on Product (sub_category_id is nullable/optional on Product).
        Schema::create('discount_categories', function (Blueprint $table) {
            $table->id();
            $table->string('discount_id');
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
        Schema::dropIfExists('discount_categories');
    }
};
