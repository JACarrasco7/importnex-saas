<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->onDelete('cascade');
            $table->string('concept');
            $table->decimal('estimated', 10, 2)->default(0);
            $table->decimal('actual', 10, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['car_id', 'concept']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_expenses');
    }
};
