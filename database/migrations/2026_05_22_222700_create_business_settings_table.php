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
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->integer('business_id')->nullable();
            $table->string('timezone')->default('Asia/Karachi');
            $table->enum('tax_type',['inclusive', 'exclusive'])->default('exclusive');
            $table->decimal('tax_rate', 8, 2)->default(18.00);
            $table->enum('date_format',['d-m-Y', 'm-d-Y', 'Y-m-d', 'd/m/Y', 'm/d/Y', 'Y/m/d'])->default('d-m-Y');
            $table->enum('time_format',['12', '24'])->default('24');
            
            $table->integer('createdby_id')->nullable();
            $table->integer('updatedby_id')->nullable();

            $table->timestamp('date_created')->nullable();
            $table->timestamp('date_updated')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('business_settings');
    }
};
