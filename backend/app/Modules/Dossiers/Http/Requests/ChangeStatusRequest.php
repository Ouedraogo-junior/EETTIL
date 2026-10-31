<?php

namespace App\Modules\Dossiers\Http\Requests;

use App\Modules\Dossiers\Enums\DossierStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ['status' => ['required', Rule::enum(DossierStatus::class)]];
    }
}