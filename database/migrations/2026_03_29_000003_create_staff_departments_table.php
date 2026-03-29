<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')
                ->constrained('staff')
                ->cascadeOnDelete();
            $table->foreignUuid('department_id')
                ->constrained('departments')
                ->cascadeOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('designation')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['staff_id']);
            $table->index(['department_id']);
            $table->index(['is_primary']);
            $table->unique(['staff_id', 'department_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_departments');
    }
};
