<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            // Null company_id means a system category shared by every tenant.
            $table->foreignId('company_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('icon')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });

        Schema::create('category_spec_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('key', 50);
            $table->string('label');
            $table->string('type', 20);
            $table->json('options')->nullable();
            $table->string('unit', 20)->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('show_on_label')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->unique(['category_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_spec_fields');
        Schema::dropIfExists('categories');
    }
};
