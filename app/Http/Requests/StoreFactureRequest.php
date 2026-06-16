<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFactureRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'exists:clients,id'],
            'montant_ht' => ['required', 'numeric', 'min:0.01'],
            'tva' => ['required', Rule::in([0, 10, 20])],
            'date_emission'=> ['required', 'date'],
            'date_echeance'=> ['required', 'date', 'after_or_equal:date_emission'],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'client_id.required' => 'Sélectionnez un client.',
            'client_id.exists' => 'Client introuvable.',
            'montant_ht.required' => 'Le montant HT est obligatoire.',
            'montant_ht.min' => 'Le montant doit être supérieur à 0.',
            'tva.in' => 'Le taux TVA doit être 0%, 10% ou 20%.',
            'date_echeance.after_or_equal'=> 'L\'échéance doit être après la date d\'émission.',
        ];
    }
}
