<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Learning paths assigned to a promotion, and the sequences opened to it
     * one by one (no row = hidden from its learners).
     */
    public function up(): void
    {
        Schema::create('learning_path_promotion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learning_path_id')->constrained()->restrictOnDelete();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['learning_path_id', 'promotion_id']);
        });

        Schema::create('sequence_releases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sequence_id')->constrained()->cascadeOnDelete();
            $table->timestamp('released_at');
            $table->foreignId('released_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unique(['promotion_id', 'sequence_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequence_releases');
        Schema::dropIfExists('learning_path_promotion');
    }
};
