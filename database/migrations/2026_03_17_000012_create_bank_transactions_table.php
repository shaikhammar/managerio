<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('bank_account_id')->constrained('accounts');
            $table->date('date');
            $table->text('description');
            $table->decimal('amount', 15, 2);    // positive = deposit, negative = withdrawal
            $table->string('reference')->nullable();
            $table->boolean('is_reconciled')->default(false);
            $table->timestamp('reconciled_at')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'bank_account_id']);
            $table->index(['business_id', 'date']);
            $table->index(['business_id', 'is_reconciled']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
    }
};
