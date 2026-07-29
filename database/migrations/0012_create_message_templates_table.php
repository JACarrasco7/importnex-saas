<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('message_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('content');
            $table->string('language')->default('es');
            $table->string('category')->nullable();
            $table->json('placeholders')->nullable();
            $table->timestamps();

            $table->index(['language', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('message_templates');
    }
};