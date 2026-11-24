<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A live session of a quiz express, joined without an account by the
     * participants, and their answers.
     */
    public function up(): void
    {
        Schema::create('express_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('express_quiz_id')->constrained()->cascadeOnDelete();
            $table->string('code', 6)->unique();
            $table->string('status', 20)->default('lobby');
            $table->unsignedSmallInteger('seconds_per_question');
            // Copy of the questions at launch: editing the quiz changes nothing here.
            $table->json('questions');
            $table->unsignedSmallInteger('current_position')->nullable();
            // Milliseconds: the speed bonus depends on it.
            $table->timestamp('question_started_at', 3)->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });

        Schema::create('express_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('express_session_id')->constrained()->cascadeOnDelete();
            $table->string('name', 30);
            // Lowercase, to refuse "Marie" next to "marie".
            $table->string('name_key', 30);
            $table->string('token_hash', 64)->unique();
            $table->unsignedInteger('score')->default(0);
            $table->timestamps();

            $table->unique(['express_session_id', 'name_key']);
        });

        Schema::create('express_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('express_participant_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->char('choice', 1);
            $table->unsignedSmallInteger('points');
            $table->timestamp('answered_at', 3);

            $table->unique(['express_participant_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('express_answers');
        Schema::dropIfExists('express_participants');
        Schema::dropIfExists('express_sessions');
    }
};
