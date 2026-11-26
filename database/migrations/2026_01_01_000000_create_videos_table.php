<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->string('title', 120);
            $table->string('slug')->unique();
            $table->string('status', 20)->default('pending');
            $table->string('original_path');
            $table->string('original_name');
            $table->unsignedBigInteger('size_bytes');
            $table->decimal('duration', 10, 3)->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->string('thumbnail_path')->nullable();
            $table->string('preview_path')->nullable();
            $table->text('error')->nullable();
            $table->unsignedBigInteger('views')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            // Catalog query: WHERE status = 'ready' ORDER BY created_at DESC
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
