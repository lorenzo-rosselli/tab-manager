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
            $table->string('band', 255)->nullable();                // gruppo con cui si suona il brano (facoltativo)
            $table->string('instrument', 10);                       // 'bass', 'guitar' o 'voice'
            $table->boolean('show_instrument')->default(false);     // scrivere lo strumento sotto il titolo del foglio
            $table->boolean('show_band')->default(false);           // scrivere il gruppo sotto il titolo del foglio
            $table->longText('tab');                                // il foglio: documento formattato in JSON (Tiptap)
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
