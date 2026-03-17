<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained();
            $table->string('type', 20);           // receipt, payment
            $table->string('number', 50);
            $table->date('date');
            $table->decimal('amount', 15, 2);
            $table->foreignId('bank_account_id')->constrained('accounts');
            $table->string('reference')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'type']);
            $table->index(['business_id', 'contact_id']);
            $table->index(['business_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
