<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_attendance_pages(): void
    {
        $this->get(route('attendances.index'))->assertRedirect(route('login'));
        $this->get(route('attendances.create'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_attendance_pages(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('attendances.index'))
            ->assertOk()
            ->assertSee('Attendance');

        $this->actingAs($user)->get(route('attendances.create'))
            ->assertOk()
            ->assertSee('Take Class Attendance');
    }

    public function test_create_page_can_load_class_register(): void
    {
        $user = User::factory()->create();
        [$academicYear, $section, $student] = $this->createClassEnrollment();

        $response = $this->actingAs($user)->get(route('attendances.create', [
            'academic_year_id' => $academicYear->id,
            'section_id' => $section->id,
            'attendance_date' => '2026-05-03',
        ]));

        $response->assertOk();
        $response->assertSee('Load Class Register');
        $response->assertSee($student->full_name);
        $response->assertSee($student->admission_no);
    }

    public function test_authenticated_users_can_save_class_attendance(): void
    {
        $user = User::factory()->create();
        [$academicYear, $section, $studentOne] = $this->createClassEnrollment([
            'first_name' => 'Aye',
            'last_name' => 'Chan',
            'admission_no' => 'ST-001',
        ]);

        $studentTwo = Student::factory()->create([
            'first_name' => 'Mya',
            'last_name' => 'Oo',
            'admission_no' => 'ST-002',
        ]);

        Enrollment::query()->create([
            'student_id' => $studentTwo->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $section->grade_id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->post(route('attendances.store'), [
            'academic_year_id' => $academicYear->id,
            'section_id' => $section->id,
            'attendance_date' => '2026-05-03',
            'attendances' => [
                [
                    'student_id' => $studentOne->id,
                    'status' => 'present',
                    'remarks' => 'On time',
                ],
                [
                    'student_id' => $studentTwo->id,
                    'status' => 'late',
                    'remarks' => 'Traffic delay',
                ],
            ],
        ]);

        $response->assertRedirect(route('attendances.create', [
            'academic_year_id' => $academicYear->id,
            'section_id' => $section->id,
            'attendance_date' => '2026-05-03',
        ]));

        $attendanceOne = Attendance::query()
            ->where('student_id', $studentOne->id)
            ->whereDate('attendance_date', '2026-05-03')
            ->first();
        $attendanceTwo = Attendance::query()
            ->where('student_id', $studentTwo->id)
            ->whereDate('attendance_date', '2026-05-03')
            ->first();

        $this->assertNotNull($attendanceOne);
        $this->assertSame('present', $attendanceOne->status);
        $this->assertSame('On time', $attendanceOne->remarks);
        $this->assertNotNull($attendanceTwo);
        $this->assertSame('late', $attendanceTwo->status);
        $this->assertSame('Traffic delay', $attendanceTwo->remarks);
    }

    public function test_store_updates_existing_attendance_records_for_the_same_date(): void
    {
        $user = User::factory()->create();
        [$academicYear, $section, $student] = $this->createClassEnrollment();

        Attendance::factory()->create([
            'student_id' => $student->id,
            'attendance_date' => '2026-05-03',
            'status' => 'present',
            'remarks' => 'Initial status',
        ]);

        $response = $this->actingAs($user)->post(route('attendances.store'), [
            'academic_year_id' => $academicYear->id,
            'section_id' => $section->id,
            'attendance_date' => '2026-05-03',
            'attendances' => [
                [
                    'student_id' => $student->id,
                    'status' => 'absent',
                    'remarks' => 'Family trip',
                ],
            ],
        ]);

        $response->assertRedirect();

        $this->assertDatabaseCount('attendances', 1);
        $attendance = Attendance::query()
            ->where('student_id', $student->id)
            ->whereDate('attendance_date', '2026-05-03')
            ->first();

        $this->assertNotNull($attendance);
        $this->assertSame('absent', $attendance->status);
        $this->assertSame('Family trip', $attendance->remarks);
    }

    public function test_store_rejects_students_outside_the_selected_class(): void
    {
        $user = User::factory()->create();
        [$academicYear, $section] = $this->createClassEnrollment();
        $otherStudent = Student::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('attendances.create'))
            ->post(route('attendances.store'), [
                'academic_year_id' => $academicYear->id,
                'section_id' => $section->id,
                'attendance_date' => '2026-05-03',
                'attendances' => [
                    [
                        'student_id' => $otherStudent->id,
                        'status' => 'present',
                        'remarks' => null,
                    ],
                ],
            ]);

        $response->assertRedirect(route('attendances.create'));
        $response->assertSessionHasErrors('attendances');
    }

    private function createClassEnrollment(array $studentOverrides = []): array
    {
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $student = Student::factory()->create($studentOverrides);

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        return [$academicYear, $section, $student];
    }
}
