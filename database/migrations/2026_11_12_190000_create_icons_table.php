<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Icons imported by the trainer: cleaned SVG files on the private disk,
     * referenced in the Markdown of any sequence as icon:{id}.
     */
    public function up(): void
    {
        Schema::create('icons', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->unique();
            $table->string('path');
            // Drawn in the colour of the text (dark mode too), or as is.
            $table->boolean('monochrome')->default(true);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('icons');
    }
};
