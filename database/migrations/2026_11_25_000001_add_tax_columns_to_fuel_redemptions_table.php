<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fuel_redemptions', function (Blueprint $table) {
            $table->decimal('tax_per_8kl', 14, 2)->default(0)->after('price_per_liter');
            $table->decimal('tax_amount', 16, 2)->default(0)->after('tax_per_8kl');
        });
    }

    public function down(): void
    {
        Schema::table('fuel_redemptions', function (Blueprint $table) {
            $table->dropColumn(['tax_per_8kl', 'tax_amount']);
        });
    }
};
