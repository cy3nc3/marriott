<?php

namespace App\Http\Controllers\Teacher\Concerns;

use App\Models\AcademicYear;
use App\Models\Section;
use App\Models\SubjectAssignment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait ResolvesTeacherAcademicYearAccess
{
    private function resolveTeacherAcademicYearOptions(int $teacherId): Collection
    {
        return AcademicYear::query()
            ->select(['academic_years.id', 'academic_years.name', 'academic_years.status', 'academic_years.start_date'])
            ->where(function (Builder $query) use ($teacherId): void {
                $query
                    ->whereHas('sections', function (Builder $sectionQuery) use ($teacherId): void {
                        $sectionQuery->where('adviser_id', $teacherId);
                    })
                    ->orWhereHas('sections.subjectAssignments.teacherSubject', function (Builder $assignmentQuery) use ($teacherId): void {
                        $assignmentQuery->where('teacher_id', $teacherId);
                    });
            })
            ->distinct()
            ->orderByDesc('academic_years.start_date')
            ->get()
            ->map(fn (AcademicYear $academicYear): array => [
                'id' => (int) $academicYear->id,
                'name' => (string) $academicYear->name,
                'status' => (string) $academicYear->status,
            ])
            ->values();
    }

    private function resolveSelectedTeacherAcademicYearId(Collection $yearOptions, ?int $requestedYearId): ?int
    {
        if ($requestedYearId && $yearOptions->pluck('id')->contains($requestedYearId)) {
            return $requestedYearId;
        }

        $ongoing = $yearOptions->firstWhere('status', 'ongoing');
        if ($ongoing) {
            return (int) $ongoing['id'];
        }

        return $yearOptions->first()['id'] ?? null;
    }

    private function resolveCurrentAcademicYearId(): ?int
    {
        return AcademicYear::query()->where('status', 'ongoing')->value('id')
            ?? AcademicYear::query()->orderByDesc('start_date')->value('id');
    }

    private function isReadOnlyHistoricalYear(?int $selectedAcademicYearId): bool
    {
        return $selectedAcademicYearId !== null
            && $this->resolveCurrentAcademicYearId() !== null
            && $selectedAcademicYearId !== $this->resolveCurrentAcademicYearId();
    }

    private function enforceCurrentYearWriteAccess(?int $selectedAcademicYearId): void
    {
        if ($this->isReadOnlyHistoricalYear($selectedAcademicYearId)) {
            abort(403);
        }
    }

    private function ensureTeacherOwnsSectionInYear(int $teacherId, int $sectionId, int $academicYearId): Section
    {
        $section = Section::query()
            ->whereKey($sectionId)
            ->where('adviser_id', $teacherId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        if (! $section) {
            abort(403);
        }

        return $section;
    }

    private function ensureTeacherOwnsAssignmentInYear(int $teacherId, int $assignmentId, int $academicYearId): SubjectAssignment
    {
        $assignment = SubjectAssignment::query()
            ->whereKey($assignmentId)
            ->whereHas('teacherSubject', function (Builder $query) use ($teacherId): void {
                $query->where('teacher_id', $teacherId);
            })
            ->whereHas('section', function (Builder $query) use ($academicYearId): void {
                $query->where('academic_year_id', $academicYearId);
            })
            ->first();

        if (! $assignment) {
            abort(403);
        }

        return $assignment;
    }
}
