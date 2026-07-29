<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('client_id')->nullable();

            // Technical specs
            $table->string('brand');
            $table->string('model');
            $table->string('version')->nullable();
            $table->string('year'); // format: "07/2016"
            $table->integer('mileage')->default(0);
            $table->string('fuel'); // Gasoline, Diesel, Hybrid, Electric
            $table->string('transmission'); // Manual, Automatic
            $table->integer('cv')->default(0);
            $table->string('displacement')->nullable();
            $table->integer('co2')->default(0);
            $table->string('consumption')->nullable();
            $table->integer('owners')->default(0);
            $table->string('doors')->nullable();
            $table->integer('seats')->default(5);
            $table->string('euro_norm')->nullable();
            $table->string('color')->nullable();
            $table->string('itv_date')->nullable();

            // Prices and costs
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->decimal('new_price', 10, 2)->default(0);
            $table->decimal('manual_tax_base', 10, 2)->default(0);
            $table->boolean('boe_confirmed')->default(false);
            $table->decimal('transport', 8, 2)->default(0);
            $table->decimal('itv_fee', 8, 2)->default(0);
            $table->decimal('coc_fee', 8, 2)->default(0);
            $table->decimal('dgt_fees', 8, 2)->default(0);
            $table->decimal('professional_fees', 10, 2)->default(0);
            $table->decimal('deposit', 10, 2)->default(0);

            // Seller info and location
            $table->string('vin', 17)->nullable();
            $table->string('vat_scenario')->default('margin');
            $table->string('seller')->nullable();
            $table->string('city')->nullable();
            $table->decimal('lat', 10, 8)->nullable();
            $table->decimal('lng', 11, 8)->nullable();

            // Status and valuation
            $table->string('status')->default('Located');
            $table->string('url_link')->nullable();
            $table->string('traffic_light')->default('neutral');
            $table->text('valuation')->nullable();
            $table->text('recommendation')->nullable();
            $table->text('description')->nullable();
            $table->json('equipment')->nullable();
            $table->json('tips')->nullable();
            $table->json('red_flags')->nullable();
            $table->json('comparables_list')->nullable();
            $table->json('fotos_json')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['organization_id', 'status']);
            $table->index(['organization_id', 'client_id']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};