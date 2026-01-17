<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', 'exists:categories,id'],
            'business_area' => ['nullable', 'string', 'max:100'],
            'priority' => ['required', Rule::in(['High', 'Medium', 'Low'])],
            'frs_status' => ['required', Rule::in(['Draft', 'Review', 'Signoff'])],
            'dev_status' => ['required', Rule::in([
                'Not Started', 'In Development', 'Testing',
                'UAT', 'Deployed', 'On Hold'
            ])],
            'current_progress' => ['nullable', 'string', 'max:100'],
            'blockers' => ['nullable', 'string', 'max:2000'],
            'owner_id' => ['nullable', 'exists:users,id'],
            'planned_release' => ['nullable', 'string', 'max:50'],
            'submission_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:submission_date'],
            'go_live_date' => ['nullable', 'date'],
            'rag_status' => ['required', Rule::in(['Green', 'Amber', 'Red'])],
            'completion_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'service_type' => ['nullable', 'string', 'max:50'],
            'remarks' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Le nom du projet est obligatoire.',
            'category_id.required' => 'La catégorie est obligatoire.',
            'category_id.exists' => 'La catégorie sélectionnée n\'existe pas.',
            'target_date.after_or_equal' => 'La date cible doit être après la date de soumission.',
            'completion_percent.min' => 'Le pourcentage doit être au minimum 0.',
            'completion_percent.max' => 'Le pourcentage doit être au maximum 100.',
        ];
    }
}
