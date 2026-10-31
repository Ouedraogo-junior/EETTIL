<?php

namespace App\Modules\Dossiers\Http\Requests;

use App\Modules\Dossiers\Enums\Service;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDossierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer', Rule::exists('clients', 'id')->whereNull('deleted_at')],
            'services' => ['required', 'array', 'min:1'],
            'services.*' => ['distinct', Rule::enum(Service::class)],
            'description' => ['nullable', 'string', 'max:1000'],
            'track' => ['sometimes', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return ['client_id' => 'client', 'services' => 'services', 'description' => 'description'];
    }

    public function messages(): array
    {
        return [
            'services.required' => 'Choisissez au moins un service.',
            'services.min' => 'Choisissez au moins un service.',
        ];
    }
}