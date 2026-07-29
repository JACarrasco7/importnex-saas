<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_documents', function (Blueprint $table) {
            $table->string('doc_key')->nullable()->after('name');
            $table->string('status')->default('pending')->after('doc_type');
            $table->string('group')->nullable()->after('status');

            $table->string('url')->nullable()->change();
            $table->string('doc_type')->nullable()->change();

            $table->index(['car_id', 'doc_key']);
            $table->index(['car_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('car_documents', function (Blueprint $table) {
            $table->dropIndex(['car_id', 'doc_key']);
            $table->dropIndex(['car_id', 'status']);
            $table->dropColumn(['doc_key', 'status', 'group']);
            $table->string('url')->nullable(false)->change();
            $table->string('doc_type')->nullable(false)->change();
        });
    }
};
