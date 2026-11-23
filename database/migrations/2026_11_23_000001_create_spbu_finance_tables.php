<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('code', 16)->unique();
            $table->string('name', 100);
            $table->decimal('margin_rate', 6, 4)->default(0.0300);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 32)->default('admin_cabang')->after('email');
            $table->foreignId('branch_id')->nullable()->after('role')->constrained('branches')->nullOnDelete();
        });

        Schema::create('fuel_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('transaction_date')->index();
            $table->string('do_number', 64)->nullable();
            $table->string('product_name', 64);
            $table->boolean('is_non_bbm')->default(false);
            $table->decimal('volume_liters', 14, 2)->default(0);
            $table->decimal('price_per_liter', 14, 2)->default(0);
            $table->decimal('total_amount', 16, 2)->default(0);
            $table->string('payment_status', 48)->default('Lunas (Tunai)');
            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('credit_sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->date('sale_date')->index();
            $table->string('client_name', 120)->index();
            $table->string('product_name', 64);
            $table->boolean('is_non_bbm')->default(false);
            $table->decimal('volume_liters', 14, 2)->default(0);
            $table->decimal('price_per_liter', 14, 2)->default(0);
            $table->decimal('total_amount', 16, 2)->default(0);
            $table->unsignedSmallInteger('credit_term_days')->default(7);
            $table->date('due_date')->nullable()->index();
            $table->string('payment_status', 32)->default('Belum Lunas')->index();
            $table->date('paid_date')->nullable()->index();
            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cash_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->string('account_type', 16)->index(); // rek1 | rek2
            $table->date('entry_date')->index();
            $table->string('description', 200);
            $table->string('category', 32)->default('shift_omzet'); // shift_omzet | tsps_tukar | non_bbm_income | operational_expense | bca_deposit | other
            $table->decimal('debit', 16, 2)->default(0);
            $table->decimal('credit', 16, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cash_projection_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->decimal('opening_balance_rek1', 16, 2)->default(0);
            $table->decimal('opening_balance_rek2', 16, 2)->default(0);
            $table->timestamps();

            $table->unique(['year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_projection_settings');
        Schema::dropIfExists('cash_entries');
        Schema::dropIfExists('credit_sales');
        Schema::dropIfExists('fuel_redemptions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('branch_id');
            $table->dropColumn('role');
        });

        Schema::dropIfExists('branches');
    }
};
