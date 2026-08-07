<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('newsletter_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('source', 32)->default('marketplace_popup');
            $table->string('ip_hash', 64)->nullable();
            $table->string('locale', 5)->default('es');
            $table->boolean('verified')->default(false);
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
            $table->index('source');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscriptions');
    }
};
