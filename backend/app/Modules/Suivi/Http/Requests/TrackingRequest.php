<?php

namespace App\Modules\Suivi\Http\Requests;

use App\Modules\Suivi\Models\DossierTracking;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TrackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->nature)) {
            $this->merge(['nature' => mb_strtoupper(trim($this->nature))]);
        }
    }

    public function rules(): array
    {
        $realDate = ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'];

        $rules = [
            'supplier_invoices' => ['nullable', 'string', 'max:500'],
            'dpi_number' => ['nullable', 'string', 'max:50', 'required_with:dpi_date'],
            'dpi_date' => $realDate,
            'bl_lta' => ['nullable', 'string', 'max:100'],
            'transit_order_number' => ['nullable', 'string', 'max:100'],
            'regime' => ['nullable', Rule::in(config('eettil.regimes'))],
            'nature' => ['required', 'string', 'max:255'],
            'packages' => ['required', 'integer', 'min:1', 'max:1000000'],
            'weight_kg' => ['required', 'numeric', 'gt:0', 'max:99999999'],
            'comments' => ['nullable', 'string', 'max:2000'],
        ];

        foreach (array_keys(DossierTracking::STEPS) as $step) {
            $rules[$step] = $realDate;
        }

        return $rules;
    }

    public function messages(): array
    {
        $messages = [
            'dpi_number.required_with' => 'Saisissez le numéro de la DPI, ou retirez sa date.',
            'dpi_date.before_or_equal' => 'On ne saisit que des dates réelles, pas de date à venir.',
            'nature.required' => 'Indiquez la nature de la marchandise.',
            'packages.required' => 'Indiquez le nombre de colis.',
            'packages.min' => 'Le nombre de colis doit être d’au moins 1.',
            'weight_kg.required' => 'Indiquez le poids en kilogrammes.',
            'weight_kg.gt' => 'Le poids doit être supérieur à 0.',
        ];

        foreach (DossierTracking::STEPS as $step => $label) {
            $messages["{$step}.before_or_equal"] = "« {$label} » : on ne saisit que des dates réelles, pas de date à venir.";
        }

        return $messages;
    }
}