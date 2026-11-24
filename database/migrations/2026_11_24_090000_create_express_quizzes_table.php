<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The quiz express: played live in the room, outside any sequence. Same
     * questions as the quiz of a sequence, with images of their own.
     */
    public function up(): void
    {
        Schema::create('express_quizzes', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            // Always timed: the speed bonus depends on it.
            $table->unsignedSmallInteger('seconds_per_question')->default(20);
            $table->timestamps();
        });

        Schema::create('express_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('express_quiz_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->timestamps();
        });

        Schema::create('express_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('express_quiz_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->text('question');
            $table->foreignId('express_image_id')->nullable()->constrained()->nullOnDelete();
            $table->string('choice_a');
            $table->string('choice_b');
            $table->string('choice_c')->nullable();
            $table->string('choice_d')->nullable();
            $table->char('correct', 1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('express_questions');
        Schema::dropIfExists('express_images');
        Schema::dropIfExists('express_quizzes');
    }
};
