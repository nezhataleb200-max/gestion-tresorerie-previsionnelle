<?php

namespace App\Imports;

use App\Models\Charge;
use App\Models\Tresorerie;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Illuminate\Support\Collection;

class FacturesAchatsImport implements
    ToCollection,
    WithHeadingRow,
    WithValidation,
    SkipsOnError
{
    use SkipsErrors;

    public array $erreurs  = [];
    public int   $importees = 0;
    public int   $ignorees  = 0;

    // Catégories acceptées
    private array $categoriesValides = [
        'loyer', 'salaires', 'impots', 'fournisseurs', 'services', 'autre'
    ];

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {

            $numeroLigne = $index + 2;

            // ── Convertir la date ────────────────────────────
            $datePrevue = $this->convertirDate($row['date_prevue'] ?? null);

            if (!$datePrevue) {
                $this->erreurs[] = "Ligne {$numeroLigne} : date prévue invalide — ignorée.";
                $this->ignorees++;
                continue;
            }

            // ── Valider la catégorie ─────────────────────────
            $categorie = strtolower(trim($row['categorie'] ?? 'autre'));
            if (!in_array($categorie, $this->categoriesValides)) {
                $categorie = 'autre'; // Valeur par défaut si catégorie inconnue
            }

            // ── Valider le type ──────────────────────────────
            $type = strtolower(trim($row['type'] ?? 'variable'));
            if (!in_array($type, ['fixe', 'variable'])) {
                $type = 'variable';
            }

            // ── Valider la récurrence ────────────────────────
            $recurrence = strtolower(trim($row['recurrence'] ?? 'aucune'));
            if (!in_array($recurrence, ['aucune', 'mensuelle', 'trimestrielle', 'annuelle'])) {
                $recurrence = 'aucune';
            }

            // ── Créer la charge ──────────────────────────────
            $charge = Charge::create([
                'user_id'    => auth()->id(),
                'libelle'    => trim($row['libelle']),
                'montant'    => (float) str_replace(',', '.', $row['montant']),
                'date_prevue'=> $datePrevue,
                'categorie'  => $categorie,
                'type'       => $type,
                'recurrence' => $recurrence,
                'payee'      => false,
            ]);

            // Si récurrente → générer les occurrences automatiquement
            if ($charge->estRecurrente()) {
                $charge->genererOccurrences();
            }

            $this->importees++;
        }

        // Recalculer le plan après tout l'import
        Tresorerie::calculerPlan(now()->year, 15000);
    }

    public function rules(): array
    {
        return [
            'libelle'    => ['required', 'string', 'max:150'],
            'montant'    => ['required', 'numeric', 'min:0.01'],
            'date_prevue'=> ['required'],
        ];
    }

    private function convertirDate($valeur): ?string
    {
        if (!$valeur) return null;

        if (is_numeric($valeur)) {
            try {
                return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($valeur)
                    ->format('Y-m-d');
            } catch (\Exception $e) {
                return null;
            }
        }

        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, trim($valeur));
            if ($date) return $date->format('Y-m-d');
        }

        return null;
    }
}