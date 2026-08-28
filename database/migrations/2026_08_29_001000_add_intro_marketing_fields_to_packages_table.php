<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->string('code')->nullable()->after('name');
            $table->string('tagline')->nullable()->after('description');
            $table->string('badge')->nullable()->after('tagline');
            $table->string('best_for')->nullable()->after('badge');
            $table->decimal('price', 18, 2)->nullable()->default(null)->change();
            $table->decimal('price_yearly', 18, 2)->nullable()->after('price');
            $table->string('currency', 10)->default('PKR')->after('price_yearly');
            $table->json('features')->nullable()->after('currency');
            $table->json('limitations')->nullable()->after('features');
            $table->json('compare')->nullable()->after('limitations');
            $table->string('support')->nullable()->after('compare');
            $table->string('cta')->nullable()->after('support');
            $table->boolean('is_custom')->default(false)->after('cta');
        });
    }

    public function down(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->dropColumn([
                'code', 'tagline', 'badge', 'best_for', 'price_yearly', 'currency',
                'features', 'limitations', 'compare', 'support', 'cta', 'is_custom',
            ]);
        });
    }
};
