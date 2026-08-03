<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('name_en');
            $table->decimal('amount', 10, 2);
            $table->string('category')->default('أخرى');
            $table->string('category_en')->default('Other');
            $table->unsignedTinyInteger('due_day');
            $table->unsignedTinyInteger('reminder_days')->default(3);
            $table->string('recurrence')->default('monthly');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_paid_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
