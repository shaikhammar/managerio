<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);           // invoice, quote, credit_note, payment, journal_entry
            $table->string('prefix', 10)->default('');
            $table->unsignedBigInteger('next_number')->default(1);
            $table->smallInteger('padding')->default(4);
            $table->timestamps();

            $table->unique(['business_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
