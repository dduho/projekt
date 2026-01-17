<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RiskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'project_id' => ['required', 'exists:projects,id'],
            'type' => ['required', Rule::in(['Risk', 'Issue'])],
            'description' => ['required', 'string', 'max:5000'],
            'impact' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'probability' => ['required', Rule::in(['Low', 'Medium', 'High'])],
            'mitigation_plan' => ['nullable', 'string', 'max:5000'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'status' => ['sometimes', Rule::in(['Open', 'In Progress', 'Mitigated', 'Closed'])],
        ];
    }

    public function messages(): array
    {
        return [
            'project_id.required' => 'Le projet est obligatoire.',
            'project_id.exists' => 'Le projet sélectionné n\'existe pas.',
            'type.required' => 'Le type est obligatoire.',
            'description.required' => 'La description est obligatoire.',
            'impact.required' => 'L\'impact est obligatoire.',
            'probability.required' => 'La probabilité est obligatoire.',
        ];
    }
}
