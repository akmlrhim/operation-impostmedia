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
            $table->foreignId('lead_stage_id')->nullable()->constrained()->nullOnDelete();

            $table->date('date_in');
            $table->string('company_name');
            $table->string('industry')->nullable();

            $table->string('contact_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('region')->nullable();
            $table->string('source')->nullable();

            $table->string('pic')->nullable();
            $table->string('pic_impost')->nullable();

            $table->decimal('estimated_value', 18, 2)->default(0);

            $table->date('last_contact_date')->nullable();
            $table->date('next_action_date')->nullable();
            $table->string('next_action')->nullable();

            $table->string('temperature')->default('cold');
            $table->text('notes')->nullable();
            $table->string('folder_url')->nullable();

            $table->string('status')->default('open');
            $table->string('lost_reason')->nullable();
            $table->foreignId('converted_client_id')->nullable()->constrained('clients')->nullOnDelete();

            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lead_stage_id', 'position']);
            $table->index(['deleted_at', 'company_name']);
            $table->index(['deleted_at', 'date_in']);
            $table->index(['status', 'next_action_date']);
            $table->index(['status', 'temperature']);
        });

        Schema::create('lead_service_package', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_package_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);

            $table->unique(['lead_id', 'service_package_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_service_package');
        Schema::dropIfExists('leads');
        Schema::dropIfExists('lead_stages');
    }
};
