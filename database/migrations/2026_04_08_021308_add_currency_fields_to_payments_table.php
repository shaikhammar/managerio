<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency_code', 3)->nullable()->after('amount');
            $table->decimal('exchange_rate', 10, 6)->default(1)->after('currency_code');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'exchange_rate']);
        });
    }
};
