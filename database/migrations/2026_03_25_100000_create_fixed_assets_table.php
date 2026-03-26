<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_account_id')->constrained('accounts');
            $table->foreignId('accumulated_depreciation_account_id')->constrained('accounts');
            $table->foreignId('depreciation_expense_account_id')->constrained('accounts');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('asset_tag')->nullable();
            $table->date('purchase_date');
            $table->decimal('purchase_cost', 12, 2);
            $table->decimal('salvage_value', 12, 2)->default(0);
            $table->unsignedInteger('useful_life_months');
            $table->string('depreciation_method')->default('straight_line');
            $table->string('status')->default('active');
            $table->date('disposal_date')->nullable();
            $table->decimal('disposal_proceeds', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
