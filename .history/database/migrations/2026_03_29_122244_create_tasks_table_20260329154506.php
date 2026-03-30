<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('due_date');
            $table->enum('priority', ['low', 'medium', 'high']);
            $table->enum('status', ['pending', 'in_progress', 'done'])->default('pending');
            $table->timestamps();

            // Prevents duplicate title on the same due_date at DB level
            $table->unique(['title', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
