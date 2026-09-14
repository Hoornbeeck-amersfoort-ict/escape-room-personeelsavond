<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('answer_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_session_id')->constrained()->cascadeOnDelete();
            $table->string('answer');
            $table->boolean('correct');
            $table->unsignedTinyInteger('attempt_number');
            $table->timestamps();

            $table->index('room_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('answer_attempts');
    }
};
