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
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('language_pair_id')->nullable()->after('tax_code_id');
            $table->unsignedBigInteger('service_type_id')->nullable()->after('language_pair_id');
            $table->string('billing_unit', 20)->nullable()->after('service_type_id');

            $table->foreign('language_pair_id')->references('id')->on('language_pairs')->nullOnDelete();
            $table->foreign('service_type_id')->references('id')->on('service_types')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_lines', function (Blueprint $table) {
            $table->dropForeign(['language_pair_id']);
            $table->dropForeign(['service_type_id']);
            $table->dropColumn(['language_pair_id', 'service_type_id', 'billing_unit']);
        });
    }
};
