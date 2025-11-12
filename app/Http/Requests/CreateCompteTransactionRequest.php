<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateCompteTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'type' => 'required|in:depot,retrait,paiement,transfert',
            'montant_transaction' => 'required|numeric|min:0.01',
        ];

        $type = $this->input('type');

        if ($type === 'transfert') {
            $rules['numero_telephone'] = 'required|string|regex:/^\+221[0-9]{9}$/';
        } elseif ($type === 'paiement') {
            $rules['code_marchand'] = 'required|string';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'type.required' => 'Le type de transaction est obligatoire.',
            'type.in' => 'Le type doit être depot, retrait, paiement ou transfert.',
            'numero_telephone.required' => 'Le numéro de téléphone est requis pour les transferts.',
            'numero_telephone.regex' => 'Le numéro de téléphone doit être au format +221XXXXXXXXX.',
            'code_marchand.required' => 'Le code marchand est requis pour les paiements.',
            'montant_transaction.required' => 'Le montant de la transaction est obligatoire.',
            'montant_transaction.numeric' => 'Le montant doit être un nombre.',
            'montant_transaction.min' => 'Le montant doit être supérieur à 0.',
        ];
    }
}