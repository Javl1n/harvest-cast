<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('crop_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->text('ai_analysis')->nullable();
            $table->string('health_status')->nullable();
            $table->text('recommendations')->nullable();
            $table->boolean('processed')->default(false);
            $table->date('image_date');
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['schedule_id', 'image_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('crop_images');
    }
};
