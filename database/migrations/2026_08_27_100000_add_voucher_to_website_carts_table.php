<?php



use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Schema\Blueprint;

use Illuminate\Support\Facades\Schema;



return new class extends Migration

{

    public function up()

    {

        Schema::table('website_carts', function (Blueprint $table) {

            $table->string('voucher_id')->nullable()->after('branch_id');

            $table->string('voucher_code', 50)->nullable()->after('voucher_id');

        });

    }



    public function down()

    {

        Schema::table('website_carts', function (Blueprint $table) {

            $table->dropColumn(['voucher_id', 'voucher_code']);

        });

    }

};

