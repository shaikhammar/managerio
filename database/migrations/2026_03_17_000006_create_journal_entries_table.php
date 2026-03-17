<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('entry_number', 50);
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('source_type', 50)->nullable();   // invoice, payment, credit_note, manual
            $table->unsignedBigInteger('source_id')->nullable();
            $table->boolean('is_posted')->default(false);
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique(['business_id', 'entry_number']);
            $table->index(['business_id', 'date']);
            $table->index(['business_id', 'source_type', 'source_id']);
            $table->index(['business_id', 'is_posted']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
