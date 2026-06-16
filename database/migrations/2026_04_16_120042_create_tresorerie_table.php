<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tresorerie', function (Blueprint $table) {
            $table->id();
            $table->integer('annee');
            $table->integer('mois')->comment('1 à 12');
            $table->decimal('solde_initial', 10, 2)->default(0.00)->comment('Trésorerie disponible au 1er du mois');
            $table->decimal('total_entrees', 10, 2)->default(0.00)->comment('Somme factures échues ce mois');
            $table->decimal('total_sorties', 10, 2)->default(0.00)->comment('Somme charges prévues ce mois');
            $table->decimal('solde_mois', 10, 2)->default(0.00)->comment('total_entrees - total_sorties');
            $table->decimal('solde_cumule', 10, 2)->default(0.00)->comment('Solde cumulé depuis le début');
            $table->timestamps();

            // Un seul enregistrement par mois/année
            $table->unique(['annee', 'mois']);
            $table->index(['annee', 'mois']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tresorerie');
    }
};