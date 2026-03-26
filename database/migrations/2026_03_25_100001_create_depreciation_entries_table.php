<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depreciation_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fixed_asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('depreciation_amount', 12, 2);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['fixed_asset_id', 'period_start']);
            $table->index('business_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depreciation_entries');
    }
};
