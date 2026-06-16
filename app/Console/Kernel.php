<?php

namespace App\Console;

use App\Models\Facture;
use App\Models\Alerte;
use App\Models\Tresorerie;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use app\Http\Controllers\DecisionController;

class Kernel extends ConsoleKernel
{
    /**
     * schedule() — définit les tâches automatiques
     *
     * Laravel Scheduler fonctionne avec un seul CRON configuré
     * sur le serveur : "* * * * * php artisan schedule:run"
     * Ce CRON s'exécute chaque minute et Laravel décide quelles
     * tâches lancer selon leur fréquence configurée ici.
     */
    protected function schedule(Schedule $schedule): void
    {
        // ─── TÂCHE 1 : Détection des factures en retard ──────
        // Tous les jours à 8h du matin
        $schedule->call(function () {

            // Cherche toutes les factures "en attente"
            // dont la date d'échéance est dépassée
            $facturesEnRetard = Facture::where('statut', 'en_attente')
                ->where('date_echeance', '<', today())
                ->get();

            foreach ($facturesEnRetard as $facture) {
                // Met à jour le statut
                $facture->update(['statut' => 'en_retard']);

                // Crée une alerte pour informer le gestionnaire
                Alerte::create([
                    'tresorerie_id' => null, // alerte liée à une facture, pas un mois
                    'type'          => 'retard_facture',
                    'niveau'        => 'warning',
                    'message'       => "Facture {$facture->numero} en retard — "
                                     . $facture->client->nom
                                     . " — Montant : "
                                     . number_format($facture->montant_ttc, 2, ',', ' ')
                                     . " MAD (échéance : "
                                     . $facture->date_echeance->format('d/m/Y') . ")",
                    'mois_concerne' => $facture->date_echeance->format('Y-m') . '-01',
                    'resolue'       => false,
                ]);
            }

        })->dailyAt('08:00')->name('detecter-factures-retard');

        // ─── TÂCHE 2 : Recalcul mensuel du plan ──────────────
        // Le 1er de chaque mois à minuit
        $schedule->call(function () {
            Tresorerie::calculerPlan(now()->year, 15000);
        })->monthly()->name('recalcul-plan-mensuel');
    }
    protected function schedule(Schedule $schedule): void
{
    // ── DÉCISION 1 : Rappels et avertissements emails ────────
    // Tous les jours à 9h du matin
    $schedule->call(function () {
        DecisionController::envoisAutomatiques();
    })->dailyAt('09:00')->name('envois-emails-automatiques');

    // ── DÉCISION 3 : Vérification seuil de sécurité ─────────
    // Tous les jours à 8h du matin
    $schedule->call(function () {
        DecisionController::verifierSeuilSecurite();
    })->dailyAt('08:00')->name('verifier-seuil-securite');

    // ── Détection retards (déjà existant) ───────────────────
    $schedule->call(function () {
        Facture::where('statut', 'en_attente')
            ->where('date_echeance', '<', today())
            ->update(['statut' => 'en_retard']);
    })->dailyAt('07:00')->name('detecter-retards');
}

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}