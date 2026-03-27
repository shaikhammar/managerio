<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cat_analysis_bands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cat_analysis_id')->constrained('cat_analyses')->cascadeOnDelete();
            $table->string('band', 30);
            $table->unsignedInteger('words')->default(0);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->timestamps();

            $table->unique(['cat_analysis_id', 'band']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cat_analysis_bands');
    }
};
