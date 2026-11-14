<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Lipa Namba per network, e.g. {"mpesa": "123456"}. A branch value overrides the company's.
        Schema::table('companies', function (Blueprint $table) {
            $table->json('payment_numbers')->nullable()->after('address');
        });
        Schema::table('branches', function (Blueprint $table) {
            $table->json('payment_numbers')->nullable()->after('whatsapp');
        });

        // A customer's "I have paid" from the QR page, approved or rejected by staff.
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 8)->unique();
            $table->string('method', 10);                       // cash | mobile
            $table->string('network', 30)->nullable();           // config('payments.networks') key
            $table->string('payment_number', 30)->nullable();    // Lipa Namba shown to the customer
            $table->string('transaction_code', 30)->nullable();
            $table->string('customer_name', 100);
            $table->string('customer_phone', 20);
            $table->unsignedInteger('listed_price');             // product price when the customer confirmed
            $table->unsignedInteger('amount');                   // what the customer says they pay
            $table->unsignedInteger('received_amount')->nullable(); // what staff confirm was received
            $table->string('status', 10)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('note', 255)->nullable();
            $table->char('ip_hash', 64);
            $table->timestamps();

            $table->index(['company_id', 'status', 'created_at']);
            $table->index(['product_id', 'status']);
            $table->index(['ip_hash', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
        Schema::table('branches', fn (Blueprint $table) => $table->dropColumn('payment_numbers'));
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn('payment_numbers'));
    }
};
