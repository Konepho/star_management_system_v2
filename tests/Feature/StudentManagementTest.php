<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Role;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_student_pages(): void
    {
        $response = $this->get(route('students.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_student_list(): void
    {
        $user = User::factory()->create();
        Student::factory()->count(2)->create();

        $response = $this->actingAs($user)->get(route('students.index'));

        $response->assertOk();
        $response->assertSee('Students');
    }

    public function test_authenticated_users_can_create_a_student(): void
    {
        $user = User::factory()->create();
        Storage::fake('public');
        $photo = $this->fakeImageUpload('student-photo.png');

        $response = $this->actingAs($user)->post(route('students.store'), [
            'admission_no' => 'STU-0001',
            'name_mm' => 'အေးချမ်း',
            'name_en' => 'Aye Chan',
            'preferred_name' => 'Ashley',
            'gender' => 'female',
            'student_type' => 'new',
            'previous_school_name' => 'Happy Kids',
            'date_of_birth' => '2015-01-01',
            'admission_date' => '2026-06-01',
            'email' => 'student@example.com',
            'contact_number' => '0912345678',
            'emergency_contact_number' => '0998765432',
            'address' => 'Yangon',
            'photo' => $photo,
            'card_color' => 'blue',
            'status' => 'active',
            'father_name' => 'U Tun',
            'father_occupation' => 'Engineer',
            'father_phone' => '0911111111',
            'father_email' => 'father@example.com',
            'mother_name' => 'Daw Mya',
            'mother_occupation' => 'Teacher',
            'mother_phone' => '0922222222',
            'mother_email' => 'mother@example.com',
            'blood_type' => 'O+',
            'allergies' => 'Peanuts',
            'medical_conditions' => 'Asthma',
            'medications' => 'Inhaler',
            'doctor_name' => 'Dr. Win',
            'doctor_phone' => '0933333333',
            'emergency_medical_note' => 'Call parent first.',
            'health_remark' => 'Needs inhaler during sports.',
        ]);

        $response->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'admission_no' => 'STU-0001',
            'name_mm' => 'အေးချမ်း',
            'name_en' => 'Aye Chan',
            'preferred_name' => 'Ashley',
            'contact_number' => '0912345678',
            'card_color' => 'blue',
        ]);

        $studentId = Student::query()->where('admission_no', 'STU-0001')->value('id');
        $student = Student::query()->findOrFail($studentId);
        $this->assertNotNull($student->photo_path);
        Storage::disk('public')->assertExists($student->photo_path);

        $this->assertDatabaseHas('guardians', [
            'student_id' => $studentId,
            'relation' => 'father',
            'name' => 'U Tun',
            'email' => 'father@example.com',
        ]);

        $this->assertDatabaseHas('guardians', [
            'student_id' => $studentId,
            'relation' => 'mother',
            'name' => 'Daw Mya',
            'email' => 'mother@example.com',
        ]);

        $this->assertDatabaseHas('student_health_profiles', [
            'student_id' => $studentId,
            'blood_type' => 'O+',
            'doctor_name' => 'Dr. Win',
        ]);

        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $studentId,
        ]);
    }

    public function test_authenticated_users_can_update_a_student(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();
        Storage::fake('public');
        $photo = $this->fakeImageUpload('updated-student-photo.png');

        $response = $this->actingAs($user)->patch(route('students.update', $student), [
            'admission_no' => $student->admission_no,
            'name_mm' => 'အပ်ဒိတ်',
            'name_en' => 'Updated Student',
            'preferred_name' => 'Update',
            'gender' => 'male',
            'student_type' => 'old',
            'previous_school_name' => 'ABC School',
            'date_of_birth' => '2014-05-01',
            'admission_date' => '2026-06-01',
            'email' => 'updated@example.com',
            'contact_number' => '0999999999',
            'emergency_contact_number' => '0988888888',
            'address' => 'Mandalay',
            'photo' => $photo,
            'card_color' => 'green',
            'status' => 'active',
            'father_name' => 'U Update',
            'father_email' => 'updated-father@example.com',
            'blood_type' => 'A+',
            'health_remark' => 'Updated health note.',
        ]);

        $response->assertRedirect(route('students.index'));

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'name_en' => 'Updated Student',
            'preferred_name' => 'Update',
            'contact_number' => '0999999999',
            'card_color' => 'green',
        ]);

        $student->refresh();
        $this->assertNotNull($student->photo_path);
        Storage::disk('public')->assertExists($student->photo_path);

        $this->assertDatabaseHas('guardians', [
            'student_id' => $student->id,
            'relation' => 'father',
            'name' => 'U Update',
            'email' => 'updated-father@example.com',
        ]);

        $this->assertDatabaseHas('student_health_profiles', [
            'student_id' => $student->id,
            'blood_type' => 'A+',
        ]);
    }

    public function test_student_profile_update_does_not_create_or_change_enrollment_records(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->patch(route('students.update', $student), [
            'admission_no' => $student->admission_no,
            'name_mm' => 'အပ်ဒိတ်',
            'name_en' => 'Updated Student',
            'admission_date' => '2026-06-01',
            'card_color' => 'yellow',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('students.index'));
        $this->assertDatabaseMissing('enrollments', [
            'student_id' => $student->id,
        ]);
    }

    public function test_authenticated_users_can_archive_a_student(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create();

        $response = $this->actingAs($user)->delete(route('students.destroy', $student));

        $response->assertRedirect(route('students.index'));
        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'status' => Student::STATUS_ARCHIVED,
        ]);
        $this->assertNotNull($student->fresh()?->archived_at);
    }

    public function test_teacher_only_sees_students_in_assigned_sections(): void
    {
        $academicYear = AcademicYear::factory()->create();
        [$teacher, $assignedSection] = $this->teacherUserForSection($academicYear);
        $otherGrade = Grade::factory()->create();
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id]);

        $visibleStudent = Student::factory()->create(['name_en' => 'Visible Student']);
        Enrollment::query()->create([
            'student_id' => $visibleStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $assignedSection->grade_id,
            'section_id' => $assignedSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $hiddenStudent = Student::factory()->create(['name_en' => 'Hidden Student']);
        Enrollment::query()->create([
            'student_id' => $hiddenStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $otherGrade->id,
            'section_id' => $otherSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($teacher)->get(route('students.index'));

        $response->assertOk();
        $response->assertSee('Visible Student');
        $response->assertDontSee('Hidden Student');
    }

    public function test_teacher_cannot_edit_student_outside_assigned_sections(): void
    {
        $academicYear = AcademicYear::factory()->create();
        [$teacher] = $this->teacherUserForSection($academicYear);
        $otherGrade = Grade::factory()->create();
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id]);
        $student = Student::factory()->create();

        Enrollment::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $otherGrade->id,
            'section_id' => $otherSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $this->actingAs($teacher)
            ->get(route('students.edit', $student))
            ->assertForbidden();
    }

    private function teacherUserForSection(AcademicYear $academicYear): array
    {
        $user = User::factory()->create();
        $teacherRole = Role::query()->where('slug', 'teacher')->firstOrFail();
        $user->roles()->sync([$teacherRole->id]);

        $grade = Grade::factory()->create();
        $section = Section::factory()->create(['grade_id' => $grade->id]);
        $staff = Staff::factory()->create(['user_id' => $user->id]);
        $staff->sectionAssignments()->create([
            'academic_year_id' => $academicYear->id,
            'section_id' => $section->id,
        ]);

        return [$user, $section];
    }

    private function fakeImageUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aF9sAAAAASUVORK5CYII=')
        );
    }
}
