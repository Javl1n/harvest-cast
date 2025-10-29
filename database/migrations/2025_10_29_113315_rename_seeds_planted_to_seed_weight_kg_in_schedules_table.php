<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->decimal('seed_weight_kg', 10, 2)->default(0)->after('hectares');
        });

        // Set reasonable default values based on typical seeding rates
        // This is a rough conversion - adjust as needed for your data
        DB::table('schedules')->update([
            'seed_weight_kg' => DB::raw('CASE
                WHEN seeds_planted <= 0 THEN 0
                WHEN seeds_planted < 50000 THEN seeds_planted * 0.0001
                WHEN seeds_planted < 100000 THEN seeds_planted * 0.00005
                ELSE seeds_planted * 0.00003
            END'),
        ]);

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('seeds_planted');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('schedules', function (Blueprint $table) {
            $table->integer('seeds_planted')->default(0)->after('hectares');
        });

        // Rough reverse conversion
        DB::table('schedules')->update([
            'seeds_planted' => DB::raw('seed_weight_kg * 20000'),
        ]);

        Schema::table('schedules', function (Blueprint $table) {
            $table->dropColumn('seed_weight_kg');
        });
    }
};
