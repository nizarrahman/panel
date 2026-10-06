<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('billing_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('egg_id')->nullable()->after('allocations');
            $table->text('startup')->nullable()->after('egg_id');
            $table->string('image')->nullable()->after('startup');
            $table->json('environment')->nullable()->after('image');
            $table->json('location_ids')->nullable()->after('environment');
            $table->unsignedInteger('swap')->default(0)->after('location_ids');
            $table->unsignedInteger('io')->default(500)->after('swap');
        });
    }

    public function down(): void
    {
        Schema::table('billing_plans', function (Blueprint $table) {
            $table->dropColumn(['egg_id', 'startup', 'image', 'environment', 'location_ids', 'swap', 'io']);
        });
    }
};
