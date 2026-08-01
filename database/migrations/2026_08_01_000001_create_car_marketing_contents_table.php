<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_marketing_contents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->string('channel');
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->json('hashtags')->nullable();
            $table->json('photo_tips')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->unique(['car_id', 'channel']);
            $table->index('status');
            $table->index('channel');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_marketing_contents');
    }
};
