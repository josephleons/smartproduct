<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('brand', 100);
            $table->string('model', 150);
            $table->text('description')->nullable();
            $table->string('condition', 20);
            $table->unsignedInteger('price');
            $table->unsignedInteger('quantity')->default(1);
            $table->string('serial_number', 100)->nullable();
            $table->unsignedSmallInteger('warranty_months')->nullable();
            $table->string('status', 20)->default('available');
            $table->string('sku', 40);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'sku']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'branch_id']);
            $table->index(['company_id', 'category_id']);
        });

        Schema::create('product_specs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('spec_field_id')->nullable()->constrained('category_spec_fields')->nullOnDelete();
            // key, label and unit are copied from the template at save time so later
            // template edits never change what an existing product shows.
            $table->string('key', 50);
            $table->string('label');
            $table->string('value');
            $table->string('unit', 20)->nullable();
            $table->boolean('show_on_label')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique(['product_id', 'key']);
        });

        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('token', 7)->unique();
            $table->unsignedInteger('scans_count')->default(0);
            $table->timestamp('last_scanned_at')->nullable();
            $table->timestamps();
        });

        Schema::create('product_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);
            $table->char('ip_hash', 64);
            $table->string('device_type', 10);
            $table->string('user_agent', 500)->nullable();
            // True when no scan from the same ip_hash + product happened in the previous 30 minutes.
            $table->boolean('is_unique')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'created_at']);
            $table->index(['product_id', 'ip_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_events');
        Schema::dropIfExists('qr_codes');
        Schema::dropIfExists('product_specs');
        Schema::dropIfExists('products');
    }
};
