<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Client;
use App\Models\Facture;
use App\Models\Charge;
use App\Models\Tresorerie;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ─── 1. Utilisateur admin ─────────────────────────────
        $user = User::create([
            'name'     => 'Gestionnaire KRS',
            'email'    => 'admin@krs.ma',
            'password' => Hash::make('password'),
            'role'     => 'admin',
        ]);

        // ─── 2. Clients ───────────────────────────────────────
        $idrissi = Client::create([
            'user_id'        => $user->id,
            'nom'            => 'SARL Idrissi Conseil',
            'type'           => 'societe',
            'email'          => 'contact@idrissi-conseil.ma',
            'telephone'      => '0522-456-789',
            'delai_paiement' => 30,
            'notes'          => 'Client fidèle, paie généralement à temps.',
        ]);

        $brahim = Client::create([
            'user_id'        => $user->id,
            'nom'            => 'Cabinet Brahim & Associés',
            'type'           => 'societe',
            'email'          => 'cabinet.brahim@gmail.com',
            'telephone'      => '0661-234-567',
            'delai_paiement' => 60,
        ]);

        $atlas = Client::create([
            'user_id'        => $user->id,
            'nom'            => 'Groupe Atlas Immobilier',
            'type'           => 'societe',
            'email'          => 'direction@atlas-immo.ma',
            'delai_paiement' => 30,
        ]);

        $elfassi = Client::create([
            'user_id'        => $user->id,
            'nom'            => 'M. El Fassi Hassan',
            'type'           => 'particulier',
            'telephone'      => '0674-111-222',
            'delai_paiement' => 15,
        ]);

        // ─── 3. Factures ──────────────────────────────────────
        $annee = now()->year;

        // Facture payée (mois passé)
        Facture::create([
            'client_id'    => $elfassi->id,
            'numero'       => "FAC-{$annee}-001",
            'montant_ht'   => 3200.00,
            'tva'          => 20,
            'montant_ttc'  => 3840.00,
            'date_emission'=> Carbon::now()->subMonth()->startOfMonth()->toDateString(),
            'date_echeance'=> Carbon::now()->subMonth()->addDays(15)->toDateString(),
            'date_paiement'=> Carbon::now()->subMonth()->addDays(12)->toDateString(),
            'statut'       => 'payee',
            'description'  => 'Consultation juridique — dossier succession',
        ]);

        // Facture en retard
        Facture::create([
            'client_id'    => $idrissi->id,
            'numero'       => "FAC-{$annee}-002",
            'montant_ht'   => 8000.00,
            'tva'          => 20,
            'montant_ttc'  => 9600.00,
            'date_emission'=> Carbon::now()->subDays(40)->toDateString(),
            'date_echeance'=> Carbon::now()->subDays(10)->toDateString(),
            'statut'       => 'en_retard',
            'description'  => 'Accompagnement fiscal T1 2026',
        ]);

        // Facture en attente ce mois
        Facture::create([
            'client_id'    => $brahim->id,
            'numero'       => "FAC-{$annee}-003",
            'montant_ht'   => 5500.00,
            'tva'          => 20,
            'montant_ttc'  => 6600.00,
            'date_emission'=> Carbon::now()->subDays(15)->toDateString(),
            'date_echeance'=> Carbon::now()->addDays(15)->toDateString(),
            'statut'       => 'en_attente',
            'description'  => 'Audit comptable mensuel',
        ]);

        // Facture future (mois prochain)
        Facture::create([
            'client_id'    => $atlas->id,
            'numero'       => "FAC-{$annee}-004",
            'montant_ht'   => 18000.00,
            'tva'          => 20,
            'montant_ttc'  => 21600.00,
            'date_emission'=> Carbon::now()->toDateString(),
            'date_echeance'=> Carbon::now()->addMonth()->addDays(20)->toDateString(),
            'statut'       => 'en_attente',
            'description'  => 'Gestion comptable T2 2026',
        ]);

        // ─── 4. Charges ───────────────────────────────────────

        // Loyer mensuel (récurrent)
        $loyer = Charge::create([
            'user_id'            => $user->id,
            'libelle'            => 'Loyer bureau',
            'montant'            => 8000.00,
            'date_prevue'        => Carbon::now()->startOfMonth()->toDateString(),
            'categorie'          => 'loyer',
            'type'               => 'fixe',
            'recurrence'         => 'mensuelle',
            'date_fin_recurrence'=> Carbon::now()->endOfYear()->toDateString(),
        ]);
        $loyer->genererOccurrences();

        // Salaires (récurrent)
        $salaires = Charge::create([
            'user_id'            => $user->id,
            'libelle'            => 'Salaires équipe',
            'montant'            => 6500.00,
            'date_prevue'        => Carbon::now()->endOfMonth()->subDays(2)->toDateString(),
            'categorie'          => 'salaires',
            'type'               => 'fixe',
            'recurrence'         => 'mensuelle',
            'date_fin_recurrence'=> Carbon::now()->endOfYear()->toDateString(),
        ]);
        $salaires->genererOccurrences();

        // Charge variable ponctuelle
        Charge::create([
            'user_id'    => $user->id,
            'libelle'    => 'Comptable externe — mission audit',
            'montant'    => 2000.00,
            'date_prevue'=> Carbon::now()->addDays(5)->toDateString(),
            'categorie'  => 'services',
            'type'       => 'variable',
            'recurrence' => 'aucune',
        ]);

        // Impôts trimestriels
        Charge::create([
            'user_id'            => $user->id,
            'libelle'            => 'TVA trimestrielle',
            'montant'            => 4200.00,
            'date_prevue'        => Carbon::now()->endOfMonth()->toDateString(),
            'categorie'          => 'impots',
            'type'               => 'fixe',
            'recurrence'         => 'trimestrielle',
            'date_fin_recurrence'=> Carbon::now()->endOfYear()->toDateString(),
        ]);

        // ─── 5. Calculer le plan de trésorerie ────────────────
        Tresorerie::calculerPlan($annee, 15000.00);
    }
}