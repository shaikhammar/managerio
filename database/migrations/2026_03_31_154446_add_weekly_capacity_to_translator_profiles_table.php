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
        Schema::table('translator_profiles', function (Blueprint $table) {
            $table->unsignedInteger('weekly_capacity')->nullable()->after('quality_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('translator_profiles', function (Blueprint $table) {
            $table->dropColumn('weekly_capacity');
        });
    }
};
