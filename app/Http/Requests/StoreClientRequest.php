<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        // true = tout utilisateur connecté peut utiliser ce formulaire
        return auth()->check();
    }

    public function rules(): array
    {
        // Récupère l'id du client en cours de modification (pour le update)
        $clientId = $this->route('client')?->id;

        return [
            'nom'            => ['required', 'string', 'max:150'],
            'type'           => ['required', Rule::in(['societe', 'particulier'])],
            'email'          => [
                'nullable', 'email', 'max:150',
                // Unique sauf pour le client qu'on modifie en ce moment
                Rule::unique('clients', 'email')->ignore($clientId),
            ],
            'telephone'      => ['nullable', 'string', 'max:20'],
            'delai_paiement' => ['required', 'integer', 'min:1', 'max:365'],
            'notes'          => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required'            => 'Le nom du client est obligatoire.',
            'type.in'                 => 'Le type doit être société ou particulier.',
            'email.unique'            => 'Cet email est déjà utilisé.',
            'delai_paiement.required' => 'Le délai de paiement est obligatoire.',
            'delai_paiement.min'      => 'Le délai doit être au moins 1 jour.',
        ];
    }
}