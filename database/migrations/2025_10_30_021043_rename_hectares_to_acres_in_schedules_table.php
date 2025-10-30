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
        // First, multiply all hectare values by 2.47105 to convert to acres
        DB::table('schedules')->update([
            'hectares' => DB::raw('hectares * 2.47105'),
        ]);

        // Then rename the column
        Schema::table('schedules', function (Blueprint $table) {
            $table->renameColumn('hectares', 'acres');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Rename the column back
        Schema::table('schedules', function (Blueprint $table) {
            $table->renameColumn('acres', 'hectares');
        });

        // Convert acres back to hectares by dividing by 2.47105
        DB::table('schedules')->update([
            'hectares' => DB::raw('hectares / 2.47105'),
        ]);
    }
};
