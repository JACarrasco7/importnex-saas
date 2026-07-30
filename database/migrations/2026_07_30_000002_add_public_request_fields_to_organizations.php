<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('name');
            $table->string('logo')->nullable()->after('slug');
            $table->boolean('is_public')->default(false)->after('logo');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['slug', 'logo', 'is_public']);
        });
    }
};
