<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Carbon;

class TeacherEligibilityService
{
    /**
     * Evaluate if a teacher is eligible to teach a subject.
     *
     * @return array{eligible: bool, status: string, reasons: string[], warnings: string[]}
     */
    public function evaluate(User $teacher, Subject $subject, ?int $academicYearId = null): array
    {
        $policyMode = (string) Setting::get('teacher_assignment_policy_mode', 'strict');
        $allowProvisional = Setting::enabled('teacher_assignment_allow_provisional', false);

        $profile = $teacher->teacherProfile;

        // Check for subject-specific qualification override
        $pivot = \App\Models\TeacherSubject::query()
            ->where('teacher_id', $teacher->id)
            ->where('subject_id', $subject->id)
            ->first();

        $status = $pivot?->qualification_status ?? $profile?->qualification_status;

        $reasons = [];
        $warnings = [];

        if (! $profile && ! $pivot) {
            return [
                'eligible' => false,
                'status' => 'not_qualified',
                'reasons' => ['Teacher has no qualification profile or subject-specific certification.'],
                'warnings' => [],
            ];
        }

        if ($status === 'not_qualified') {
            $reasons[] = 'Teacher is marked as not qualified for this subject.';
        }

        if ($policyMode === 'strict') {
            if ($status !== 'fully_qualified') {
                $reasons[] = 'Strict policy requires fully qualified teachers.';
            }
        } elseif ($policyMode === 'transitional') {
            if ($status === 'provisionally_qualified' && ! $allowProvisional) {
                $reasons[] = 'Provisional assignments are currently disabled by policy.';
            }
        }

        if ($status === 'provisionally_qualified') {
            $expiry = $pivot?->qualification_status ? null : $profile?->provisional_until;

            if ($expiry) {
                if (Carbon::parse($expiry)->isPast()) {
                    $reasons[] = 'Provisional period has expired on '.Carbon::parse($expiry)->format('M d, Y').'.';
                } else {
                    $warnings[] = 'Provisional qualification expires on '.Carbon::parse($expiry)->format('M d, Y').'.';
                }
            }
        }

        if (! $pivot?->qualification_status && $profile) {
            $subjectTag = $this->subjectCompetencyTag($subject);
            $competencyTags = collect($profile->subject_competency_tags ?? [])
                ->map(fn (mixed $tag): string => strtoupper(trim((string) $tag)))
                ->filter()
                ->values();

            if ($subjectTag !== '' && ! $competencyTags->contains($subjectTag)) {
                $reasons[] = "Teacher profile is not tagged as qualified for {$subjectTag}.";
            }

            $gradeBand = $this->subjectGradeBand($subject);
            $gradeBands = collect($profile->grade_band_eligibility ?? [])
                ->map(fn (mixed $band): string => strtolower(trim((string) $band)))
                ->filter()
                ->values();

            if ($gradeBand !== '' && $gradeBands->isNotEmpty() && ! $gradeBands->contains($gradeBand)) {
                $reasons[] = "Teacher profile is not cleared for the {$gradeBand} grade band.";
            }
        }

        $eligible = count($reasons) === 0;

        return [
            'eligible' => $eligible,
            'status' => $eligible ? 'eligible' : 'ineligible',
            'reasons' => $reasons,
            'warnings' => $warnings,
        ];
    }

    private function subjectCompetencyTag(Subject $subject): string
    {
        return strtoupper((string) preg_replace('/\d+$/', '', (string) $subject->subject_code));
    }

    private function subjectGradeBand(Subject $subject): string
    {
        $levelOrder = (int) ($subject->gradeLevel?->level_order ?? 0);

        if ($levelOrder >= 7 && $levelOrder <= 10) {
            return 'junior_high';
        }

        if ($levelOrder >= 1 && $levelOrder <= 6) {
            return 'elementary';
        }

        if ($levelOrder >= 11 && $levelOrder <= 12) {
            return 'senior_high';
        }

        return '';
    }
}
