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
        Schema::table('product_variations', function (Blueprint $table) {
            // Standard this specific barcode was generated/entered as, independent of the
            // business's current default setting so changing the setting later never
            // retroactively affects already-generated codes.
            $table->enum('barcode_type', ['CODE128', 'EAN13', 'EAN8', 'UPCA'])->nullable()->after('barcode');

            // true = a manufacturer-provided value the user typed/scanned in; false = system-generated.
            $table->boolean('barcode_is_manual')->default(false)->after('barcode_type');

            // The payload string encoded into the QR (barcode value / SKU / internal reference).
            // The QR image itself is rendered on demand from this string, never stored.
            $table->string('qr_code', 500)->nullable()->after('barcode_is_manual');

            $table->timestamp('barcode_generated_at')->nullable()->after('qr_code');
            $table->timestamp('qr_generated_at')->nullable()->after('barcode_generated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('product_variations', function (Blueprint $table) {
            $table->dropColumn([
                'barcode_type',
                'barcode_is_manual',
                'qr_code',
                'barcode_generated_at',
                'qr_generated_at',
            ]);
        });
    }
};
