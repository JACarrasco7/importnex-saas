<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('car_checklists', function (Blueprint $table) {
            $table->string('kind')->default('inspection')->after('item_key');
            $table->string('priority')->nullable()->after('kind');
            $table->string('section')->nullable()->after('priority');

            $table->index(['car_id', 'kind']);
            $table->index(['car_id', 'priority']);
        });
    }

    public function down(): void
    {
        Schema::table('car_checklists', function (Blueprint $table) {
            $table->dropIndex(['car_id', 'kind']);
            $table->dropIndex(['car_id', 'priority']);
            $table->dropColumn(['kind', 'priority', 'section']);
        });
    }
};
