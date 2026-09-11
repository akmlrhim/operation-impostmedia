<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('type');
            $table->string('category');
            $table->decimal('amount', 15, 2);
            $table->date('transaction_date');
            $table->text('notes')->nullable();

            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['deleted_at', 'type', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_transactions');
    }
};
