<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained()->cascadeOnDelete();
            $table->smallInteger('year');
            $table->tinyInteger('month')->nullable()->comment('1–12 for monthly budgets; null for annual total');
            $table->decimal('amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'account_id', 'year', 'month']);
            $table->index(['business_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_budgets');
    }
};
