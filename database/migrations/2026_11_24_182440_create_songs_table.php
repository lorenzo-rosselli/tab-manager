<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('songs', function (Blueprint $table) {
            $table->id();
            $table->string('title', 255);
            $table->string('artist', 255);
            $table->string('instrument', 10);   // 'bass' oppure 'guitar'
            $table->string('tuning', 50);       // es. "E A D G"
            $table->longText('tab');            // le righe della tabulatura
            $table->text('notes')->nullable();  // facoltative
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('songs');
    }
};
