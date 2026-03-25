<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurring_journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->enum('frequency', ['monthly', 'quarterly', 'annually']);
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->date('next_run_date');
            $table->timestamp('last_run_at')->nullable();
            $table->tinyInteger('day_of_month')->default(1)->comment('Day of the month to post (1–28)');
            $table->boolean('is_active')->default(true);
            $table->json('template_lines')->comment('Array of {account_id, description, debit, credit}');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['business_id', 'is_active', 'next_run_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurring_journal_entries');
    }
};
