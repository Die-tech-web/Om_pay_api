<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Tu peux ajouter ici une logique d’autorisation si besoin
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required|string|max:100',
            'prenom' => 'required|string|max:100',
            'telephone' => 'required|string|unique:users,telephone',
            'email' => 'required|email|unique:users,email',
            'code_pin' => 'required|string|min:4|max:6',
        ];
    }

    public function messages(): array
    {
        return [
            'telephone.unique' => 'Ce numéro de téléphone est déjà enregistré.',
            'email.unique' => 'Cet email est déjà enregistré.',
            'email.email' => 'Veuillez saisir une adresse email valide.',
        ];
    }
}
