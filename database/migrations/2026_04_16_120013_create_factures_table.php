<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('restrict');
            $table->foreignId('tresorerie_id')->constrained('tresorerie')->onDelete('restrict');
            $table->string('numero', 20)->unique()->comment('Format: FAC-2026-001');
            $table->decimal('montant_ht', 10, 2);
            $table->decimal('tva', 5, 2)->default(20.00)->comment('Taux TVA en %');
            $table->decimal('montant_ttc', 10, 2)->comment('Calculé automatiquement: HT * (1 + TVA/100)');
            $table->date('date_emission');
            $table->date('date_echeance')->comment('Détermine le mois dans le plan de trésorerie');
            $table->date('date_paiement')->nullable()->comment('Remplie quand statut = payee');
            $table->enum('statut', ['en_attente', 'payee', 'en_retard'])->default('en_attente');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('client_id');
            $table->index('tresorerie_id');
            $table->index('date_echeance');
            $table->index('statut');
            $table->index(['date_echeance', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('factures');
    }
};