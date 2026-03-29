<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_specialties', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('staff_id')
                ->constrained('staff')
                ->cascadeOnDelete();
            $table->string('specialty_name');
            $table->string('specialty_code')->nullable();
            $table->text('description')->nullable();
            $table->date('certification_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('issuing_body')->nullable();
            $table->string('certificate_number')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['staff_id']);
            $table->index(['specialty_name']);
            $table->index(['is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_specialties');
    }
};
