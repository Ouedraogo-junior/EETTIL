<?php

namespace App\Modules\Paiements\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paid_on' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000000'],
            'mode' => ['required', Rule::in(['especes', 'virement', 'mobile_money'])],
            'account_id' => ['required', 'integer', Rule::exists('accounts', 'id')->where('active', true)],
            'reference' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'paid_on' => 'date',
            'amount' => 'montant',
            'mode' => 'mode de paiement',
            'account_id' => 'compte',
            'reference' => 'référence',
        ];
    }

    public function messages(): array
    {
        return [
            'paid_on.before_or_equal' => 'La date du paiement ne peut pas être dans le futur.',
            'amount.min' => 'Saisissez un montant supérieur à zéro.',
        ];
    }
}