<?php

namespace App\Modules\Facturation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vat_enabled' => ['nullable', 'boolean'],
            'vat_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'global_discount_kind' => ['nullable', Rule::in(['pct', 'amt'])],
            'global_discount_value' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['present', 'array', 'max:200'],
            'lines.*.block' => ['required', Rule::in(['D', 'P'])],
            'lines.*.label' => ['nullable', 'string', 'max:255'],
            'lines.*.unit' => ['nullable', 'string', 'max:30'],
            'lines.*.quantity' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'lines.*.unit_price' => ['nullable', 'integer', 'min:0', 'max:100000000000'],
            'lines.*.discount_kind' => ['nullable', Rule::in(['pct', 'amt'])],
            'lines.*.discount_value' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}