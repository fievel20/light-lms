<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One quiz per sequence, launched from the text with [Quizz]. Questions
     * have two to four choices (C and D optional) and may show an image of
     * the sequence.
     */
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title');
            // Null: no time limit.
            $table->unsignedSmallInteger('seconds_per_question')->nullable();
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->text('question');
            $table->foreignId('sequence_image_id')->nullable()->constrained()->nullOnDelete();
            $table->string('choice_a');
            $table->string('choice_b');
            $table->string('choice_c')->nullable();
            $table->string('choice_d')->nullable();
            $table->char('correct', 1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
