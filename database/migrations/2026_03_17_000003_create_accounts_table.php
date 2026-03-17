<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->string('type', 20);         // asset, liability, equity, revenue, expense
            $table->string('sub_type', 50)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();
            $table->timestamps();

            $table->unique(['business_id', 'code']);
            $table->index(['business_id', 'type']);
            $table->index(['business_id', 'sub_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
