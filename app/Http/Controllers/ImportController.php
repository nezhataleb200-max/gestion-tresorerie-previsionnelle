<?php

namespace App\Http\Controllers;

use App\Imports\FacturesVentesImport;
use App\Imports\FacturesAchatsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class ImportController extends Controller
{
    /**
     * index() — affiche la page d'import avec les deux formulaires
     */
    public function index()
    {
        return view('import.index');
    }

    /**
     * importVentes() — importe les factures de ventes (entrées d'argent)
     * Ces données alimentent la colonne "Entrées" du plan de trésorerie
     */
    public function importVentes(Request $request)
    {
        // 1. Valider que le fichier est bien un Excel
        $request->validate([
            'fichier' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:5120', // 5 Mo maximum
            ],
        ], [
            'fichier.required' => 'Veuillez sélectionner un fichier.',
            'fichier.mimes'    => 'Le fichier doit être au format Excel (.xlsx, .xls) ou CSV.',
            'fichier.max'      => 'Le fichier ne doit pas dépasser 5 Mo.',
        ]);

        // 2. Créer l'instance de l'importeur
        $import = new FacturesVentesImport();

        // 3. Lancer l'import (Laravel lit le fichier ligne par ligne)
        Excel::import($import, $request->file('fichier'));

        // 4. Préparer le message de résultat
        $message = "{$import->importees} facture(s) de vente importée(s) avec succès.";
        if ($import->ignorees > 0) {
            $message .= " {$import->ignorees} ligne(s) ignorée(s) pour erreur.";
        }

        // 5. Rediriger avec le résumé
        return redirect()->route('import.index')
            ->with('success', $message)
            ->with('erreurs_import', $import->erreurs);
    }

    /**
     * importAchats() — importe les factures d'achats (sorties d'argent)
     * Ces données alimentent la colonne "Sorties" du plan de trésorerie
     */
    public function importAchats(Request $request)
    {
        $request->validate([
            'fichier' => [
                'required',
                'file',
                'mimes:xlsx,xls,csv',
                'max:5120',
            ],
        ]);

        $import = new FacturesAchatsImport();

        Excel::import($import, $request->file('fichier'));

        $message = "{$import->importees} facture(s) d'achat importée(s) avec succès.";
        if ($import->ignorees > 0) {
            $message .= " {$import->ignorees} ligne(s) ignorée(s).";
        }

        return redirect()->route('import.index')
            ->with('success', $message)
            ->with('erreurs_import', $import->erreurs);
    }

    /**
     * telechargerModeleVentes() — télécharge le fichier Excel modèle
     * pour que l'utilisateur sache quel format utiliser
     */
    public function telechargerModeleVentes()
    {
        return response()->download(
            public_path('templates/modele_factures_ventes.xlsx'),
            'modele_factures_ventes.xlsx'
        );
    }

    public function telechargerModeleAchats()
    {
        return response()->download(
            public_path('templates/modele_factures_achats.xlsx'),
            'modele_factures_achats.xlsx'
        );
    }
}