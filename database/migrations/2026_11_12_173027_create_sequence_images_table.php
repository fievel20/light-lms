<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Images used in a sequence: files on the private disk, referenced in the
     * Markdown as image:{id}.
     */
    public function up(): void
    {
        Schema::create('sequence_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 50);
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sequence_images');
    }
};
