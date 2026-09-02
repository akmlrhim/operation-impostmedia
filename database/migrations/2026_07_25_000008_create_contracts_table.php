<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();

            $table->string('type')->default('mou');
            $table->string('title');
            $table->longText('scope')->nullable();

            $table->longText('body')->nullable();

            $table->json('ai_clauses')->nullable();

            $table->longText('document_body')->nullable();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('signing_place')->nullable();
            $table->date('signed_date')->nullable();

            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('tax_amount', 18, 2)->default(0);
            $table->decimal('value', 18, 2)->default(0);

            $table->text('payment_terms')->nullable();
            $table->string('billing_cycle')->default('one_time');
            $table->date('next_invoice_date')->nullable();

            $table->string('first_party_name')->nullable();
            $table->string('first_party_position')->nullable();
            $table->string('second_party_name')->nullable();
            $table->string('second_party_position')->nullable();

            $table->string('status')->default('draft');
            $table->string('file_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['client_id', 'status']);
            $table->index(['status', 'next_invoice_date']);
            $table->index(['status', 'end_date']);
            $table->index(['deleted_at', 'start_date']);
        });

        Schema::create('contract_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_package_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('quantity', 12, 2)->default(1);
            $table->string('unit')->default('paket');
            $table->decimal('unit_price', 18, 2)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            $table->index(['contract_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_items');
        Schema::dropIfExists('contracts');
    }
};
