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
        Schema::create('rate_card_volume_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rate_card_id')->constrained('rate_cards')->cascadeOnDelete();
            $table->unsignedInteger('minimum_words');
            $table->decimal('unit_rate', 10, 4);
            $table->timestamps();

            $table->unique(['rate_card_id', 'minimum_words']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rate_card_volume_tiers');
    }
};
