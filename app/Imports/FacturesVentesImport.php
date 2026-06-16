<?php

namespace App\Imports;

use App\Models\Facture;
use App\Models\Client;
use App\Models\Tresorerie;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;

class FacturesVentesImport implements
    ToCollection,
    WithHeadingRow,   // La 1ère ligne = noms des colonnes
    WithValidation,   // Validation de chaque ligne
    SkipsOnError      // Saute les lignes invalides au lieu de tout arrêter
{
    use SkipsErrors;

    // Stocke les erreurs pour les afficher après l'import
    public array $erreurs = [];
    public int   $importees = 0;
    public int   $ignorees  = 0;

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            $numeroLigne = $index + 2; // +2 car ligne 1 = en-tête

            // ── 1. Chercher ou créer le client ──────────────
            // On cherche par email, ou on le crée s'il n'existe pas
            $client = Client::firstOrCreate(
                ['email' => trim($row['client_email'] ?? '')],
                [
                    'user_id'        => auth()->id(),
                    'nom'            => trim($row['client_nom'] ?? 'Client importé'),
                    'type'           => 'societe',
                    'delai_paiement' => 30,
                ]
            );

            // ── 2. Calculer le TTC ──────────────────────────
            $ht  = (float) str_replace(',', '.', $row['montant_ht'] ?? 0);
            $tva = (float) str_replace(',', '.', $row['tva'] ?? 20);
            $ttc = round($ht * (1 + $tva / 100), 2);

            // ── 3. Convertir les dates ──────────────────────
            // Excel stocke les dates en numérique — on convertit
            $dateEmission = $this->convertirDate($row['date_emission'] ?? null);
            $dateEcheance = $this->convertirDate($row['date_echeance'] ?? null);

            if (!$dateEmission || !$dateEcheance) {
                $this->erreurs[] = "Ligne {$numeroLigne} : date invalide — ligne ignorée.";
                $this->ignorees++;
                continue; // Saute cette ligne
            }

            // ── 4. Créer la facture ──────────────────────────
            Facture::create([
                'client_id'     => $client->id,
                'numero'        => Facture::genererNumero(),
                'montant_ht'    => $ht,
                'tva'           => $tva,
                'montant_ttc'   => $ttc,
                'date_emission' => $dateEmission,
                'date_echeance' => $dateEcheance,
                'statut'        => 'en_attente',
                'description'   => trim($row['description'] ?? ''),
            ]);

            $this->importees++;
        }

        // ── 5. Recalculer le plan après tout l'import ───────
        Tresorerie::calculerPlan(now()->year, 15000);
    }

    // Règles de validation par colonne
    public function rules(): array
    {
        return [
            'client_nom'    => ['required', 'string'],
            'client_email'  => ['required', 'email'],
            'montant_ht'    => ['required', 'numeric', 'min:0.01'],
            'tva'           => ['required', 'numeric', 'in:0,10,20'],
            'date_emission' => ['required'],
            'date_echeance' => ['required'],
        ];
    }

    // Messages d'erreur en français
    public function customValidationMessages(): array
    {
        return [
            'montant_ht.required' => 'Le montant HT est obligatoire.',
            'client_email.email'  => 'L\'email client n\'est pas valide.',
            'tva.in'              => 'La TVA doit être 0, 10 ou 20.',
        ];
    }

    // Convertit une date Excel (numérique ou texte) en format Y-m-d
    private function convertirDate($valeur): ?string
    {
        if (!$valeur) return null;

        // Si c'est un nombre Excel (ex: 46000), convertir
        if (is_numeric($valeur)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valeur)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        // Si c'est déjà une chaîne de date, essayer plusieurs formats
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, trim($valeur));
            if ($date) return $date->format('Y-m-d');
        }

        return null;
    }
}