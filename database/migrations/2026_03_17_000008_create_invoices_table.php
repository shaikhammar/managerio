<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained();
            $table->string('type', 20);           // quote, invoice, credit_note, purchase_invoice
            $table->string('number', 50);
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('draft');
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('balance_due', 15, 2)->default(0);
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->string('currency_code', 3)->default('USD');
            $table->decimal('exchange_rate', 10, 6)->default(1);
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'type', 'number']);
            $table->index(['business_id', 'type', 'status']);
            $table->index(['business_id', 'contact_id']);
            $table->index(['business_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
