<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->json('images')->nullable();
            $table->string('answer');
            $table->json('alternative_answers')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['game_id', 'name']);
            $table->index('active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
