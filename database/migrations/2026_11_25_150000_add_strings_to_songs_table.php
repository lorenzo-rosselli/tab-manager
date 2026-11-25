<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Aggiunge il numero di corde ai brani.
 * È una migration nuova (non una modifica a quella che crea la tabella): così "php artisan migrate"
 * aggiunge la colonna anche ai database che contengono già dei brani, senza cancellarli.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->unsignedTinyInteger('strings')->nullable()->after('instrument'); // 4, 5, 6 o vuoto
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('songs', function (Blueprint $table) {
            $table->dropColumn('strings');
        });
    }
};
