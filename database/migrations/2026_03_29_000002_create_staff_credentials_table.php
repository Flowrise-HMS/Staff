<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Staff\Enums\CredentialStatus;
use Modules\Staff\Enums\CredentialType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_credentials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')
                ->constrained('staff')
                ->cascadeOnDelete();
            $table->enum('credential_type', CredentialType::values());
            $table->string('credential_number')->nullable();
            $table->string('issuing_authority')->nullable();
            $table->string('issuing_country')->nullable();
            $table->string('issuing_state')->nullable();
            $table->date('issue_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->enum('status', CredentialStatus::values())->default('pending');
            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('verification_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('document_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['staff_id']);
            $table->index(['credential_type']);
            $table->index(['status']);
            $table->index(['expiry_date']);
            $table->unique(['staff_id', 'credential_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_credentials');
    }
};
