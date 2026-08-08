<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            // Enriched valuation data (JSON, compatible con SaaS multi-tenant)
            // Research: 9 aspects with source URL, value, and date
            $table->json('research')->nullable();
            // Pros: list of positive points with weight (high/medium/low)
            $table->json('pros')->nullable();
            // Cons: list of negative points with weight (high/medium/low)
            $table->json('cons')->nullable();

            // Structured verdict
            // verdict: Buy / Buy if price drops / Doubtful / Discard
            $table->string('verdict')->nullable();
            // verdict_confidence: high / medium / low
            $table->string('verdict_confidence')->nullable();
            // verdict_reasoning: reasoning in prose
            $table->text('verdict_reasoning')->nullable();
            // verdict_changes: what would change the verdict
            $table->text('verdict_changes')->nullable();
            // verdict_at: when the verdict was issued
            $table->timestamp('verdict_at')->nullable();

            // Market data
            // market_avg: average price of comparables
            $table->decimal('market_avg', 10, 2)->nullable();
            // market_min: minimum price
            $table->decimal('market_min', 10, 2)->nullable();
            // market_max: maximum price
            $table->decimal('market_max', 10, 2)->nullable();
            // estimated_saving: savings compared to buying in Spain
            $table->decimal('estimated_saving', 10, 2)->nullable();

            // Research metadata
            // research_source: 'chat' | 'app' | 'manual'
            $table->string('research_source')->nullable();
            // schema_version: version of the contract (for compatibility)
            $table->tinyInteger('schema_version')->default(1);

            // Index for filtering by verdict
            $table->index('verdict');
            // Index for filtering by research source
            $table->index('research_source');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropIndex(['verdict']);
            $table->dropIndex(['research_source']);
            $table->dropColumn([
                'research',
                'pros',
                'cons',
                'verdict',
                'verdict_confidence',
                'verdict_reasoning',
                'verdict_changes',
                'verdict_at',
                'market_avg',
                'market_min',
                'market_max',
                'estimated_saving',
                'research_source',
                'schema_version',
            ]);
        });
    }
};
