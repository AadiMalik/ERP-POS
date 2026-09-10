<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Business-scoped product tags - used both for internal search/filtering
     * and as keywords fed into social-share text (Facebook/LinkedIn/etc.
     * unfurl better with topical keywords than a bare product name).
     */
    public function up()
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('tag_id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('name');
            $table->string('slug');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_deleted')->default(false);
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();
            $table->integer('deletedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
            $table->timestamp('date_deleted')->nullable();

            $table->unique(['business_id', 'slug'], 'tags_business_id_slug_unique');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tags');
    }
};
