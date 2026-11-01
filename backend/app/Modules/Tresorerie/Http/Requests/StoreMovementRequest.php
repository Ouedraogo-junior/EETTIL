<?php

namespace App\Modules\Tresorerie\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMovementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'direction' => ['required', Rule::in(['in', 'out'])],
            'moved_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'label' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'integer', 'exists:movement_categories,id'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('active', true)],
            'dossier_id' => ['nullable', 'integer', Rule::exists('dossiers', 'id')->whereNull('deleted_at')],
        ];
    }

    public function attributes(): array
    {
        return [
            'moved_on' => 'date',
            'label' => 'libellé',
            'category_id' => 'catégorie',
            'amount' => 'montant',
            'account_id' => 'compte',
            'dossier_id' => 'dossier',
        ];
    }

    public function messages(): array
    {
        return [
            'moved_on.before_or_equal' => 'La date ne peut pas être dans le futur.',
            'amount.min' => 'Saisissez un montant supérieur à zéro.',
            'label.required' => 'Saisissez un libellé.',
        ];
    }
}