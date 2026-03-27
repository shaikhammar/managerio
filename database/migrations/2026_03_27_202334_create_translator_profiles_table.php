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
        Schema::create('translator_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('availability')->default('available');
            $table->unsignedTinyInteger('quality_rating')->nullable();
            $table->text('quality_notes')->nullable();
            $table->json('specialisations')->nullable();
            $table->json('cat_tools')->nullable();
            $table->json('certifications')->nullable();
            $table->timestamps();

            $table->index('business_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('translator_profiles');
    }
};
