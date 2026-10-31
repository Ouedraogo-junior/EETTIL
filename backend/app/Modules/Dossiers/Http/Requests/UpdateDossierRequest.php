<?php

namespace App\Modules\Dossiers\Http\Requests;

use App\Modules\Dossiers\Enums\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['distinct', Rule::enum(Service::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'services.required' => 'Choisissez au moins un service.',
            'services.min' => 'Choisissez au moins un service.',
        ];
    }
}