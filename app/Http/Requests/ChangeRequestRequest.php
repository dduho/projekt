<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ChangeRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'change_type' => ['required', Rule::in(['Scope Change', 'Schedule Change', 'Budget Change', 'Resource Change'])],
            'description' => ['required', 'string', 'max:5000'],
            'impact_analysis' => ['nullable', 'string', 'max:5000'],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Le projet est obligatoire.',
            'project_id.exists' => 'Le projet sélectionné n\'existe pas.',
            'change_type.required' => 'Le type de changement est obligatoire.',
            'description.required' => 'La description est obligatoire.',
        ];
    }
}
