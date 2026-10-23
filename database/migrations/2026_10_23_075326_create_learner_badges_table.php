<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Badges earned by learners; once earned, a badge is never taken back.
     */
    public function up(): void
    {
        Schema::create('learner_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('learner_id')->constrained('users')->cascadeOnDelete();
            $table->string('badge', 40);
            $table->timestamp('earned_at');
            $table->timestamp('seen_at')->nullable();

            $table->unique(['learner_id', 'badge']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('learner_badges');
    }
};
