<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('client_contact_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->onDelete('cascade');
            $table->date('contact_date');
            $table->string('channel');
            $table->text('summary');
            $table->timestamps();

            $table->index(['client_id', 'contact_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('client_contact_logs');
    }
};