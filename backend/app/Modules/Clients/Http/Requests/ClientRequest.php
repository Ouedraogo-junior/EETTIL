<?php

namespace App\Modules\Clients\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // l'accès est déjà protégé par auth:sanctum (Admin et Gérant)
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->ifu)) {
            $this->merge(['ifu' => strtoupper(trim($this->ifu))]);
        }
    }

    public function rules(): array
    {
        $current = $this->route('client'); // null à la création

        return [
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'ifu' => [
                'nullable', 'string', 'max:50',
                Rule::unique('clients', 'ifu')->whereNull('deleted_at')->ignore($current?->id),
            ],
            'rccm' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nom',
            'phone' => 'téléphone',
            'email' => 'e-mail',
            'address' => 'adresse',
            'ifu' => 'IFU',
            'rccm' => 'RCCM',
            'notes' => 'notes',
        ];
    }
}