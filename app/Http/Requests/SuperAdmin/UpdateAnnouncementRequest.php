<?php

namespace App\Http\Requests\SuperAdmin;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAnnouncementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $role = $this->user()?->role;
        $roleValue = is_string($role) ? $role : $role?->value;

        return in_array($roleValue, [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::REGISTRAR->value,
            UserRole::FINANCE->value,
            UserRole::TEACHER->value,
        ], true);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'type' => ['nullable', 'string', Rule::in(['notice', 'event'])],
            'response_mode' => [
                'nullable',
                'string',
                Rule::in(['none', 'ack_rsvp']),
            ],
            'target_roles' => ['nullable', 'array'],
            'target_roles.*' => ['string', Rule::in($this->roleValues())],
            'target_user_ids' => ['nullable', 'array'],
            'target_user_ids.*' => ['integer', 'exists:users,id'],
            'audience_academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'publish_at' => ['nullable', 'date'],
            'event_starts_at' => [Rule::requiredIf($this->input('type', 'notice') === 'event'), 'nullable', 'date'],
            'event_ends_at' => ['nullable', 'date', 'after_or_equal:event_starts_at'],
            'response_deadline_at' => ['nullable', 'date', 'before_or_equal:event_starts_at'],
            'expires_at' => ['nullable', 'date', 'after_or_equal:publish_at'],
            'attachments' => ['nullable', 'array', 'max:5'],
            'attachments.*' => ['file', 'max:10240', 'mimes:jpg,jpeg,png,webp,gif,pdf,doc,docx,xls,xlsx,csv,txt'],
            'removed_attachment_ids' => ['nullable', 'array'],
            'removed_attachment_ids.*' => ['integer', 'exists:announcement_attachments,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'attachments.max' => 'You can upload up to 5 attachments per announcement.',
            'attachments.*.max' => 'Each attachment must be 10MB or smaller.',
            'attachments.*.mimes' => 'Allowed attachment types: images, PDF, Word, Excel, CSV, and TXT files.',
            'expires_at.after_or_equal' => 'Expiry must be on or after the publish schedule.',
            'event_starts_at.required' => 'Event start date and time is required for event announcements.',
            'event_ends_at.after_or_equal' => 'Event end time must be on or after the event start time.',
            'response_deadline_at.before_or_equal' => 'Response deadline must be on or before the event start time.',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function roleValues(): array
    {
        $publisherRole = $this->user()?->role;
        $publisherRoleValue = is_string($publisherRole) ? $publisherRole : $publisherRole?->value;

        $roles = collect(UserRole::cases());
        if ($publisherRoleValue !== UserRole::SUPER_ADMIN->value) {
            $roles = $roles->reject(
                fn (UserRole $role) => $role === UserRole::SUPER_ADMIN
            );
        }

        return $roles
            ->map(fn (UserRole $role): string => $role->value)
            ->all();
    }
}
