<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            // Moneda por defecto (EUR) y locale (separado del del usuario) para formateo.
            $table->string('currency', 3)->default('EUR')->after('slug');
            $table->string('locale', 5)->default('es')->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['currency', 'locale']);
        });
    }
};
