<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Files of a sequence to download (PDF, documents…), linked from its
     * text with [Text](fichier:{id}); on the private disk like its images.
     */
    public function up(): void
    {
        Schema::create('sequence_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sequence_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedInteger('size');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sequence_attachments');
    }
};
