<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->string('zk_user_id')->nullable()->after('staff_number');
            $table->index('zk_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropIndex(['zk_user_id']);
            $table->dropColumn('zk_user_id');
        });
    }
};
