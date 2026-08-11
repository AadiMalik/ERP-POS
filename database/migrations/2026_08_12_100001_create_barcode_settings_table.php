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
        Schema::create('barcode_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_id')->nullable();

            // Independent enable/disable per requirement — both default enabled.
            $table->boolean('enable_barcode')->default(true);
            $table->boolean('enable_qr_code')->default(true);

            // Barcode generation behaviour
            $table->enum('barcode_type', ['CODE128', 'EAN13', 'EAN8', 'UPCA'])->default('CODE128');
            $table->string('barcode_prefix', 20)->nullable();
            $table->unsignedTinyInteger('code128_length')->default(12);

            // QR generation behaviour
            $table->enum('qr_data_source', ['barcode_value', 'sku', 'internal_reference'])->default('internal_reference');
            $table->unsignedSmallInteger('qr_size_px')->default(200);
            $table->string('qr_error_correction', 1)->default('M');

            // Label/print design options (size, alignment, which fields to show, spacing, etc.)
            $table->json('label_config')->nullable();

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
        Schema::dropIfExists('barcode_settings');
    }
};
