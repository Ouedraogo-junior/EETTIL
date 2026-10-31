<?php

namespace App\Modules\Facturation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => [Rule::requiredIf($this->isMethod('post')), Rule::in(['proforma', 'facture'])],
            'from_proforma_id' => ['nullable', 'integer', 'exists:documents,id'],
            'issued_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'currency' => ['required', Rule::in(['XOF', 'EUR', 'USD'])],
            'exchange_rate' => ['nullable', 'numeric', 'gt:0', 'max:99999999'],
            'caf_value' => ['nullable', 'integer', 'min:0'],
            'containers_20' => ['nullable', 'integer', 'min:0', 'max:999'],
            'containers_40' => ['nullable', 'integer', 'min:0', 'max:999'],
            'vat_enabled' => ['required', 'boolean'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'global_discount_kind' => ['nullable', Rule::in(['pct', 'amt'])],
            'global_discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['required', 'array', 'min:1', 'max:200'],
            'lines.*.block' => ['required', Rule::in(['D', 'P'])],
            'lines.*.label' => ['required', 'string', 'max:255'],
            'lines.*.unit' => ['nullable', 'string', 'max:30'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:9999999'],
            'lines.*.unit_price' => ['required', 'integer', 'min:0', 'max:100000000000'],
            'lines.*.discount_kind' => ['nullable', Rule::in(['pct', 'amt'])],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'issued_on' => 'date du document',
            'currency' => 'devise',
            'exchange_rate' => 'taux de change',
            'caf_value' => 'valeur CAF',
            'containers_20' => 'conteneurs 20’',
            'containers_40' => 'conteneurs 40’',
            'vat_rate' => 'taux de TVA',
            'lines.*.label' => 'désignation',
            'lines.*.quantity' => 'quantité',
            'lines.*.unit_price' => 'prix unitaire',
            'lines.*.discount_value' => 'remise',
        ];
    }

    public function messages(): array
    {
        return [
            'lines.required' => 'Ajoutez au moins une ligne.',
            'lines.min' => 'Ajoutez au moins une ligne.',
            'lines.*.label.required' => 'Saisissez une désignation pour chaque ligne.',
            'lines.*.quantity.gt' => 'La quantité doit être supérieure à zéro.',
            'issued_on.before_or_equal' => 'La date du document ne peut pas être dans le futur.',
        ];
    }
}