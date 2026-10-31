<?php

namespace App\Modules\Facturation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['reason' => ['required', 'string', 'min:3', 'max:500']];
    }

    public function attributes(): array
    {
        return ['reason' => 'motif'];
    }

    public function messages(): array
    {
        return ['reason.required' => 'Indiquez le motif de l’annulation.'];
    }
}