<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('intercompany_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_business_id')->constrained('businesses');
            $table->foreignId('target_business_id')->constrained('businesses');
            $table->foreignId('source_account_id')->constrained('accounts');
            $table->foreignId('target_account_id')->constrained('accounts');
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('description', 255);
            $table->string('reference', 100)->nullable();
            $table->foreignId('source_journal_entry_id')->nullable()->constrained('journal_entries');
            $table->foreignId('target_journal_entry_id')->nullable()->constrained('journal_entries');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['source_business_id', 'date']);
            $table->index(['target_business_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('intercompany_transactions');
    }
};
