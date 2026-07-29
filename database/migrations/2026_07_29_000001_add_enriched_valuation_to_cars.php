<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->json('research')->nullable()->after('red_flags');
            $table->json('pros')->nullable()->after('research');
            $table->json('cons')->nullable()->after('pros');
            $table->string('verdict')->nullable()->after('cons');
            $table->string('verdict_confidence')->nullable()->after('verdict');
            $table->text('verdict_reasoning')->nullable()->after('verdict_confidence');
            $table->text('verdict_changes')->nullable()->after('verdict_reasoning');
            $table->timestamp('verdict_at')->nullable()->after('verdict_changes');
            $table->decimal('market_avg', 10, 2)->nullable()->after('verdict_at');
            $table->decimal('market_min', 10, 2)->nullable()->after('market_avg');
            $table->decimal('market_max', 10, 2)->nullable()->after('market_min');
            $table->decimal('estimated_saving', 10, 2)->nullable()->after('market_max');
            $table->string('research_source')->nullable()->after('estimated_saving');
            $table->unsignedTinyInteger('schema_version')->default(1)->after('research_source');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn([
                'research', 'pros', 'cons',
                'verdict', 'verdict_confidence', 'verdict_reasoning', 'verdict_changes', 'verdict_at',
                'market_avg', 'market_min', 'market_max', 'estimated_saving',
                'research_source', 'schema_version',
            ]);
        });
    }
};
