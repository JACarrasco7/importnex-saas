<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->after('organization_id')->constrained()->onDelete('set null');
        });

        Schema::table('client_contact_logs', function (Blueprint $table) {
            $table->foreignId('organization_id')->nullable()->after('client_id')->constrained()->onDelete('cascade');
            $table->index(['organization_id', 'contact_date']);
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
            $table->dropColumn('client_id');
        });

        Schema::table('client_contact_logs', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropColumn('organization_id');
        });
    }
};
