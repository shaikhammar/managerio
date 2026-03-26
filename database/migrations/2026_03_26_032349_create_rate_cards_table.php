<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rate_cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('type', 20); // default | client | translator
            $table->foreignId('contact_id')->nullable()->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('language_pair_id')->constrained('language_pairs')->cascadeOnDelete();
            $table->foreignId('service_type_id')->constrained('service_types')->cascadeOnDelete();
            $table->decimal('unit_rate', 10, 4);
            $table->string('unit', 20)->default('word');
            $table->decimal('minimum_fee', 10, 2)->nullable();
            $table->decimal('rush_multiplier', 5, 2)->nullable();
            $table->decimal('rush_fixed_surcharge', 10, 2)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['business_id', 'type']);
            $table->index(['business_id', 'contact_id']);
            $table->unique(['business_id', 'type', 'contact_id', 'language_pair_id', 'service_type_id'], 'rate_cards_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_cards');
    }
};
