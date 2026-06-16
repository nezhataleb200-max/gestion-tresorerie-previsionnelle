<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChargeRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Seuls les utilisateurs connectés peuvent soumettre ce formulaire
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'libelle'            => ['required', 'string', 'max:150'],
            'montant'            => ['required', 'numeric', 'min:0.01'],
            'date_prevue'        => ['required', 'date'],
            'categorie'          => ['required', Rule::in([
                                        'loyer','salaires','impots',
                                        'fournisseurs','services','autre'
                                    ])],
            'type'               => ['required', Rule::in(['fixe', 'variable'])],
            // recurrence est obligatoire seulement si type = fixe
            'recurrence'         => ['required', Rule::in([
                                        'aucune','mensuelle',
                                        'trimestrielle','annuelle'
                                    ])],
            // date_fin_recurrence est optionnelle, mais doit être après date_prevue
            'date_fin_recurrence'=> [
                'nullable',
                'date',
                'after:date_prevue',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required'             => 'Le libellé est obligatoire.',
            'montant.required'             => 'Le montant est obligatoire.',
            'montant.min'                  => 'Le montant doit être supérieur à 0.',
            'date_prevue.required'         => 'La date prévue est obligatoire.',
            'categorie.in'                 => 'Catégorie non valide.',
            'type.in'                      => 'Le type doit être fixe ou variable.',
            'recurrence.in'                => 'Récurrence non valide.',
            'date_fin_recurrence.after'    => 'La date de fin doit être après la date prévue.',
        ];
    }
}