<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function after(): array
    {
        return [
            function (\Illuminate\Validation\Validator $validator) {
                if ($this->has(['section_id', 'day', 'start_time', 'end_time'])) {
                    $overlap = \App\Models\ClassSchedule::query()
                        ->where('section_id', $this->section_id)
                        ->where('day', $this->day)
                        ->where('start_time', '<', $this->end_time)
                        ->where('end_time', '>', $this->start_time)
                        ->exists();

                    if ($overlap) {
                        $validator->errors()->add(
                            'start_time',
                            'The scheduled time overlaps with an existing schedule for this section.'
                        );
                    }
                }
                
                if ($this->has(['teacher_id', 'day', 'start_time', 'end_time']) && $this->type === 'academic') {
                    $teacherOverlap = \App\Models\ClassSchedule::query()
                        ->whereHas('subjectAssignment.teacherSubject', function ($query) {
                            $query->where('teacher_id', $this->teacher_id);
                        })
                        ->where('day', $this->day)
                        ->where('start_time', '<', $this->end_time)
                        ->where('end_time', '>', $this->start_time)
                        ->exists();

                    if ($teacherOverlap) {
                        $validator->errors()->add(
                            'teacher_id',
                            'The selected teacher already has a class scheduled during this time.'
                        );
                    }
                }
            }
        ];
    }

    public function rules(): array
    {
        return [
            'section_id' => ['required', 'exists:sections,id'],
            'subject_assignment_id' => ['nullable', 'exists:subject_assignments,id'],
            'subject_id' => ['nullable', 'required_if:type,academic', 'exists:subjects,id'],
            'teacher_id' => ['nullable', 'required_if:type,academic', 'exists:users,id'],
            'type' => ['required', 'string', 'in:academic,break,ceremony'],
            'label' => ['nullable', 'string', 'max:255'],
            'day' => ['required', Rule::in([
                'Monday',
                'Tuesday',
                'Wednesday',
                'Thursday',
                'Friday',
                'Saturday',
                'Sunday',
            ])],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ];
    }
}
