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
        Schema::create('translator_profile_language_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('translator_profile_id')->constrained()->cascadeOnDelete();
            $table->foreignId('language_pair_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['translator_profile_id', 'language_pair_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translator_profile_language_pairs');
    }
};
