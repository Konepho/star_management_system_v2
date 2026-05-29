<?php

namespace App\Support;

use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class SectionScope
{
    public static function accessibleSectionIds(?User $user, ?int $academicYearId = null): ?Collection
    {
        if (! $user || ! $user->requiresSectionScope()) {
            return null;
        }

        return $user->assignedSectionIds($academicYearId);
    }

    public static function restrictEnrollmentQuery(Builder $query, ?User $user, string $table = 'enrollments', ?int $academicYearId = null): Builder
    {
        $sectionIds = self::accessibleSectionIds($user, $academicYearId);

        if ($sectionIds === null) {
            return $query;
        }

        if ($sectionIds->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn($table . '.section_id', $sectionIds->all());
    }

    public static function assignmentMap(?User $user): ?Collection
    {
        if (! $user || ! $user->requiresSectionScope()) {
            return null;
        }

        $assignments = $user->staff?->relationLoaded('sectionAssignments')
            ? $user->staff->sectionAssignments
            : $user->staff?->sectionAssignments()->get();

        if (! $assignments) {
            return collect();
        }

        return $assignments
            ->groupBy('academic_year_id')
            ->map(fn (Collection $items) => $items->pluck('section_id')->map(fn (mixed $id) => (int) $id)->unique()->values());
    }

    public static function restrictStudentEnrollmentScope(Builder $query, ?User $user, string $relation = 'enrollments'): Builder
    {
        $assignmentMap = self::assignmentMap($user);

        if ($assignmentMap === null) {
            return $query;
        }

        if ($assignmentMap->isEmpty()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas($relation, function (Builder $enrollmentQuery) use ($assignmentMap): void {
            $enrollmentQuery->where(function (Builder $scopedQuery) use ($assignmentMap): void {
                foreach ($assignmentMap as $academicYearId => $sectionIds) {
                    $scopedQuery->orWhere(function (Builder $yearQuery) use ($academicYearId, $sectionIds): void {
                        $yearQuery
                            ->where('academic_year_id', (int) $academicYearId)
                            ->whereIn('section_id', $sectionIds->all());
                    });
                }
            });
        });
    }

    public static function canAccessStudent(?User $user, Student $student, string $relation = 'enrollments'): bool
    {
        $assignmentMap = self::assignmentMap($user);

        if ($assignmentMap === null) {
            return true;
        }

        if ($assignmentMap->isEmpty()) {
            return false;
        }

        return $student->{$relation}()
            ->where(function (Builder $scopedQuery) use ($assignmentMap): void {
                foreach ($assignmentMap as $academicYearId => $sectionIds) {
                    $scopedQuery->orWhere(function (Builder $yearQuery) use ($academicYearId, $sectionIds): void {
                        $yearQuery
                            ->where('academic_year_id', (int) $academicYearId)
                            ->whereIn('section_id', $sectionIds->all());
                    });
                }
            })
            ->exists();
    }

    public static function canAccessAcademicYearSection(?User $user, ?int $academicYearId, ?int $sectionId): bool
    {
        $sectionIds = self::accessibleSectionIds($user, $academicYearId);

        if ($sectionIds === null) {
            return true;
        }

        if (! $academicYearId || ! $sectionId || $sectionIds->isEmpty()) {
            return false;
        }

        return $sectionIds->contains((int) $sectionId);
    }
}
