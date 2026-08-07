<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('author_name', 120);
            $table->string('author_role', 120)->nullable();
            $table->string('author_company', 120)->nullable();
            $table->text('content');
            $table->unsignedTinyInteger('rating')->default(5); // 1-5
            $table->string('avatar_url', 500)->nullable();
            $table->string('car_purchased', 200)->nullable(); // ej: "BMW 320d 2019"
            $table->boolean('is_approved')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['organization_id', 'is_approved', 'sort_order']);
            $table->index(['organization_id', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
