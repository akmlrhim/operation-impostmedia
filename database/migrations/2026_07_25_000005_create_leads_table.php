<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_stages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->default('#64748b');
            $table->unsignedInteger('position')->default(0);
            $table->string('type')->default('open');
            $table->timestamps();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_stage_id')->constrained()->restrictOnDelete();

            $table->string('company_name');
            $table->string('contact_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('source')->nullable();

            $table->decimal('estimated_value', 18, 2)->default(0);
            $table->date('expected_close_date')->nullable();
            $table->string('priority')->default('medium');
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('open');
            $table->string('lost_reason')->nullable();
            $table->foreignId('converted_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->timestamp('converted_at')->nullable();

            $table->unsignedInteger('position')->default(0);
            $table->timestamp('next_follow_up_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lead_stage_id', 'position']);
            $table->index(['status', 'owner_id']);
            $table->index(['deleted_at', 'company_name']);
            $table->index(['status', 'next_follow_up_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_stages');
    }
};
