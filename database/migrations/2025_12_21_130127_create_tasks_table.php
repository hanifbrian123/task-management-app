<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('due')->nullable();
            $table->text('note')->nullable();
            $table->tinyInteger('priority')->nullable();
            $table->enum('status', ['yet', 'done'])->default('yet');
            $table->dateTime('reminder_at')->nullable();
            $table->foreignId('user_id')
                  ->constrained()
                  ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
