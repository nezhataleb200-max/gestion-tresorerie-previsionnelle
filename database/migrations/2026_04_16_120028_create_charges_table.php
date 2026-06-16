<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('charges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('libelle', 150);
            $table->decimal('montant', 10, 2);
            $table->date('date_prevue')->comment('Détermine le mois dans le plan de trésorerie');
            $table->enum('categorie', [
                'loyer',
                'salaires',
                'impots',
                'fournisseurs',
                'services',
                'autre'
            ])->default('autre');
            $table->enum('type', ['fixe', 'variable'])->default('variable');
            $table->enum('recurrence', [
                'aucune',
                'mensuelle',
                'trimestrielle',
                'annuelle'
            ])->default('aucune');
            $table->date('date_fin_recurrence')->nullable();
            $table->boolean('payee')->default(false);
            $table->timestamps();

            $table->index('user_id');
            $table->index('date_prevue');
            $table->index('categorie');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('charges');
    }
};