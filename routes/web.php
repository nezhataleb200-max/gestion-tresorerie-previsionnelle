<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ClientController;      
use App\Http\Controllers\FactureController; 
use App\Http\Controllers\ChargeController ; 
use App\Http\Controllers\AlerteController ;  
use App\Http\Controllers\TresorerieController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SimulationController;
use App\Http\Controllers\PrevisionnelController;
use App\Http\Controllers\DecisionController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    Route::resource('clients', ClientController::class);
    
    Route::resource('factures', FactureController::class);
    
    // Route spéciale pour marquer une facture comme payée
    Route::patch('factures/{facture}/payer', [FactureController::class, 'marquerPayee'])
         ->name('factures.payer');

         // Charges — CRUD complet
    Route::resource('charges', ChargeController::class);

// Action supplémentaire : marquer une charge comme payée
    Route::patch('charges/{charge}/payer', [ChargeController::class, 'marquerPayee'])
     ->name('charges.payer');

     // Plan de trésorerie
Route::get('plan', [TresorerieController::class, 'index'])
     ->name('plan.index');
Route::get('plan/export', [TresorerieController::class, 'exportPdf'])
     ->name('plan.export');

// Alertes
Route::get('alertes', [AlerteController::class, 'index'])
     ->name('alertes.index');
Route::patch('alertes/{alerte}/resoudre', [AlerteController::class, 'resoudre'])
     ->name('alertes.resoudre');

     // Import Excel
Route::get('import', [ImportController::class, 'index'])->name('import.index');
Route::post('import/ventes', [ImportController::class, 'importVentes'])->name('import.ventes');
Route::post('import/achats', [ImportController::class, 'importAchats'])->name('import.achats');
Route::get('import/modele-ventes', [ImportController::class, 'telechargerModeleVentes'])->name('import.modele.ventes');
Route::get('import/modele-achats', [ImportController::class, 'telechargerModeleAchats'])->name('import.modele.achats');

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])
     ->name('dashboard');
Route::get('/dashboard/export', [DashboardController::class, 'exportPdf'])
     ->name('dashboard.export');

     // Simulation de scénarios
Route::get('simulation', [SimulationController::class, 'index'])
     ->name('simulation.index');
Route::post('simulation', [SimulationController::class, 'simuler'])
     ->name('simulation.simuler');
     // Prévisionnel (paiement des charges)
Route::get('/previsionnel', [PrevisionnelController::class, 'index'])->name('previsionnel.index');
// Centre de décisions
Route::get('decisions', [DecisionController::class, 'index'])
     ->name('decisions.index');

// Décision 1 : Emails
Route::post('decisions/rappel/{facture}', [DecisionController::class, 'envoyerRappel'])
     ->name('decisions.rappel');
Route::post('decisions/avertissement/{facture}', [DecisionController::class, 'envoyerAvertissement'])
     ->name('decisions.avertissement');

// Décision 2 : Reporter charges
Route::post('decisions/reporter/{charge}', [DecisionController::class, 'reporterCharge'])
     ->name('decisions.reporter');
Route::post('decisions/reporter-tout', [DecisionController::class, 'reporterToutesChargesNonCritiques'])
     ->name('decisions.reporter-tout');
     Route::get('plan/export', [TresorerieController::class, 'exportPdf'])->name('plan.export');
    // ================================================
});

require __DIR__.'/auth.php';