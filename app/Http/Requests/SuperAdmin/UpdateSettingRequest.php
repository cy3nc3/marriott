<?php

namespace App\Http\Requests\SuperAdmin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'school_name' => 'nullable|string|max:255',
            'school_id' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:1000',
            'division' => 'nullable|string|max:255',
            'district' => 'nullable|string|max:255',
            'principal_name' => 'nullable|string|max:255',
            'maintenance_mode' => 'nullable|boolean',
            'parent_portal' => 'nullable|boolean',
            'backup_interval' => 'nullable|string|in:week,month,custom',
            'backup_interval_days' => 'nullable|integer|min:1|max:365|required_if:backup_interval,custom',
            'backup_on_quarter' => 'nullable|boolean',
            'backup_on_year_end' => 'nullable|boolean',
            'teacher_assignment_policy_mode' => 'nullable|string|in:strict,transitional',
            'teacher_assignment_allow_provisional' => 'nullable|boolean',
            'teacher_assignment_allow_admin_override' => 'nullable|boolean',
            'teacher_assignment_require_override_reason' => 'nullable|boolean',
            'logo' => 'nullable|image|max:2048',
            'header' => 'nullable|image|max:4096',
            'run_backup' => 'nullable|boolean',
            'restore_file' => 'nullable|string',
        ];
    }
}
