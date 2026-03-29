<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Staff\Enums\EmploymentStatus;
use Modules\Staff\Enums\StaffType;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')
                ->nullable()
                ->unique()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('staff_number')->unique();
            $table->string('title')->nullable();
            $table->string('first_name');
            $table->string('middle_name')->nullable();
            $table->string('last_name');
            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();
            $table->json('address')->nullable();
            $table->json('contact')->nullable();
            $table->json('emergency_contact')->nullable();
            $table->enum('staff_type', StaffType::values())->default('full_time');
            $table->enum('employment_status', EmploymentStatus::values())->default('pending_verification');
            $table->date('hire_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->text('termination_reason')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['staff_number']);
            $table->index(['employment_status']);
            $table->index(['staff_type']);
            $table->index(['hire_date']);
            $table->index(['user_id']);
            $table->index(['first_name', 'last_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff');
    }
};
