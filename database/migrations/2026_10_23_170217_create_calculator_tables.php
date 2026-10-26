<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calculator_types', function (Blueprint $t) {
            $t->id();
            $t->string('code')->unique();
            $t->string('name');
            $t->text('description');
            $t->string('image');
            $t->boolean('active')->default(true);
            $t->unsignedInteger('sort_order')->default(0);
            $t->timestamps();
        });
        foreach (['pricing_packages', 'calculator_property_types', 'calculator_materials', 'calculator_services', 'calculator_addons'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->id();
                $t->foreignId('calculator_type_id')->constrained()->restrictOnDelete();
                $t->string('code')->unique();
                $t->string('name');
                $t->text('description')->nullable();
                $t->string('unit')->default('item');
                $t->string('billing_key');
                $t->unsignedBigInteger('min_rate')->default(0);
                $t->unsignedBigInteger('max_rate')->default(0);
                $t->decimal('min_quantity', 12, 2)->default(0);
                $t->decimal('max_quantity', 12, 2)->default(10000);
                $t->json('config')->nullable();
                $t->boolean('active')->default(false)->index();
                $t->boolean('is_demo')->default(false);
                $t->unsignedInteger('sort_order')->default(0);
                $t->timestamps();
            });
        }
        Schema::create('calculator_pricing_rules', function (Blueprint $t) {
            $t->id();
            $t->foreignId('calculator_type_id')->unique()->constrained()->restrictOnDelete();
            $t->string('name');
            $t->decimal('min_area', 12, 2)->default(100);
            $t->decimal('max_area', 12, 2)->default(20000);
            $t->string('area_basis')->default('carpet');
            $t->unsignedInteger('tax_bps')->default(0);
            $t->unsignedInteger('contingency_bps')->default(0);
            $t->text('inclusions');
            $t->text('exclusions');
            $t->text('disclaimer');
            $t->boolean('active')->default(false);
            $t->boolean('is_demo')->default(false);
            $t->timestamps();
        });
        Schema::create('calculator_settings', function (Blueprint $t) {
            $t->id();
            $t->string('key')->unique();
            $t->text('value')->nullable();
            $t->timestamps();
        });
        Schema::create('calculator_price_versions', function (Blueprint $t) {
            $t->id();
            $t->string('hash', 64)->unique();
            $t->json('snapshot');
            $t->timestamps();
        });
        Schema::create('calculator_estimates', function (Blueprint $t) {
            $t->id();
            $t->uuid('public_id')->unique();
            $t->string('reference')->unique();
            $t->foreignId('calculator_type_id')->constrained()->restrictOnDelete();
            $t->foreignId('pricing_package_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('calculator_property_type_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('calculator_price_version_id')->constrained()->restrictOnDelete();
            $t->json('inputs');
            $t->json('breakdown');
            $t->unsignedBigInteger('min_total');
            $t->unsignedBigInteger('max_total');
            $t->boolean('is_demo')->default(false);
            $t->timestamps();
        });
        Schema::create('calculator_estimate_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('calculator_estimate_id')->constrained()->cascadeOnDelete();
            $t->string('label');
            $t->string('group');
            $t->string('unit');
            $t->decimal('quantity', 12, 2);
            $t->unsignedBigInteger('min_rate');
            $t->unsignedBigInteger('max_rate');
            $t->unsignedBigInteger('min_total');
            $t->unsignedBigInteger('max_total');
            $t->boolean('included')->default(false);
            $t->timestamps();
        });
        Schema::create('calculator_leads', function (Blueprint $t) {
            $t->id();
            $t->foreignId('calculator_estimate_id')->unique()->constrained()->restrictOnDelete();
            $t->string('name', 120);
            $t->string('phone', 22);
            $t->string('email', 200);
            $t->string('location', 200);
            $t->string('project_type', 100);
            $t->string('timeline', 100);
            $t->string('consultation_method', 30);
            $t->timestamp('consented_at');
            $t->string('status')->default('new')->index();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        foreach (['calculator_leads', 'calculator_estimate_items', 'calculator_estimates', 'calculator_price_versions', 'calculator_settings', 'calculator_pricing_rules', 'calculator_addons', 'calculator_services', 'calculator_materials', 'calculator_property_types', 'pricing_packages', 'calculator_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
