<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StudentIdCardManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_id_card_pages(): void
    {
        $this->get(route('student-id-cards.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_student_id_card_list(): void
    {
        $user = User::factory()->create();
        Student::factory()->create();

        $response = $this->actingAs($user)->get(route('student-id-cards.index'));

        $response->assertOk();
        $response->assertSee('ID Cards');
        $response->assertSee('Student Cards');
    }

    public function test_student_id_cards_can_be_filtered_by_class(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create();
        $grade = Grade::factory()->create(['name' => 'Grade 5']);
        $otherGrade = Grade::factory()->create(['name' => 'Grade 6']);
        $section = Section::factory()->create(['grade_id' => $grade->id, 'name' => 'A']);
        $otherSection = Section::factory()->create(['grade_id' => $otherGrade->id, 'name' => 'B']);

        $targetStudent = Student::factory()->create([
            'name_en' => 'Aye Chan',
            'admission_no' => 'STU-1001',
            'preferred_name' => null,
        ]);
        $otherStudent = Student::factory()->create([
            'name_en' => 'Moe Thiri',
            'admission_no' => 'STU-1002',
            'preferred_name' => null,
        ]);

        Enrollment::query()->create([
            'student_id' => $targetStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        Enrollment::query()->create([
            'student_id' => $otherStudent->id,
            'academic_year_id' => $academicYear->id,
            'grade_id' => $otherGrade->id,
            'section_id' => $otherSection->id,
            'enrollment_date' => '2026-06-01',
            'status' => Enrollment::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->get(route('student-id-cards.index', [
            'audience' => 'students',
            'academic_year_id' => $academicYear->id,
            'grade_id' => $grade->id,
            'section_id' => $section->id,
        ]));

        $response->assertOk();
        $response->assertSee('Aye Chan');
        $response->assertDontSee('Moe Thiri');
    }

    public function test_authenticated_users_can_view_staff_id_card_list(): void
    {
        $user = User::factory()->create();
        Staff::factory()->create([
            'staff_no' => 'STF-1001',
            'first_name' => 'Aye',
            'last_name' => 'Moe',
            'department' => 'Finance',
        ]);

        $response = $this->actingAs($user)->get(route('student-id-cards.index', [
            'audience' => 'staff',
        ]));

        $response->assertOk();
        $response->assertSee('Staff Cards');
        $response->assertSee('STF-1001');
    }

    public function test_staff_id_cards_can_be_filtered_by_department(): void
    {
        $user = User::factory()->create();
        Staff::factory()->create([
            'staff_no' => 'STF-1001',
            'first_name' => 'Aye',
            'last_name' => 'Moe',
            'department' => 'Finance',
            'designation' => 'Cashier',
        ]);
        Staff::factory()->create([
            'staff_no' => 'STF-1002',
            'first_name' => 'Ko',
            'last_name' => 'Min',
            'department' => 'Library',
            'designation' => 'Librarian',
        ]);

        $response = $this->actingAs($user)->get(route('student-id-cards.index', [
            'audience' => 'staff',
            'department' => 'Finance',
        ]));

        $response->assertOk();
        $response->assertSee('STF-1001');
        $response->assertDontSee('STF-1002');
    }

    public function test_authenticated_users_can_view_printable_student_id_card(): void
    {
        $user = User::factory()->create();
        Storage::fake('public');
        $photo = $this->fakeImageUpload('student-card-photo.png');
        $student = Student::factory()->create([
            'name_en' => 'Aye Chan',
            'admission_no' => 'STU-1001',
            'photo_path' => $photo->store('student-photos', 'public'),
        ]);

        $response = $this->actingAs($user)->get(route('student-id-cards.print', $student));

        $response->assertOk();
        $response->assertSee('Aye Chan');
        $response->assertSee('STU-1001');
        $response->assertSee('<svg', false);
        $response->assertSee('data:image', false);
    }

    public function test_authenticated_users_can_view_printable_staff_id_card(): void
    {
        $user = User::factory()->create();
        Storage::fake('public');
        $photo = $this->fakeImageUpload('staff-card-photo.png');
        $staff = Staff::factory()->create([
            'staff_no' => 'STF-1001',
            'first_name' => 'Aye',
            'last_name' => 'Moe',
            'photo_path' => $photo->store('staff-photos', 'public'),
        ]);

        $response = $this->actingAs($user)->get(route('student-id-cards.print-staff', $staff));

        $response->assertOk();
        $response->assertSee('Aye Moe');
        $response->assertSee('STF-1001');
        $response->assertSee('<svg', false);
        $response->assertSee('data:image', false);
    }

    public function test_printable_staff_id_card_prefers_user_name_when_legacy_staff_name_looks_like_phone_number(): void
    {
        $user = User::factory()->create();
        $staffUser = User::factory()->create(['name' => 'Daw Hnin Ei']);
        $staff = Staff::factory()->create([
            'user_id' => $staffUser->id,
            'staff_no' => 'STF-1003',
            'first_name' => '0912345678',
            'last_name' => '',
            'phone' => '0912345678',
        ]);

        $response = $this->actingAs($user)->get(route('student-id-cards.print-staff', $staff));

        $response->assertOk();
        $response->assertSee('Daw Hnin Ei');
        $response->assertDontSee('<div class="student-name">0912345678</div>', false);
    }

    public function test_authenticated_users_can_print_selected_student_id_cards(): void
    {
        $user = User::factory()->create();
        $studentOne = Student::factory()->create(['name_en' => 'Aye Chan', 'preferred_name' => null]);
        $studentTwo = Student::factory()->create(['name_en' => 'Moe Thiri', 'preferred_name' => null]);

        $response = $this->actingAs($user)->post(route('student-id-cards.bulk-print'), [
            'audience' => 'students',
            'selected_ids' => [$studentOne->id, $studentTwo->id],
        ]);

        $response->assertOk();
        $response->assertSee('Aye Chan');
        $response->assertSee('Moe Thiri');
        $response->assertSee('Print ID Cards');
    }

    public function test_authenticated_users_can_print_selected_staff_id_cards(): void
    {
        $user = User::factory()->create();
        $staffOne = Staff::factory()->create(['first_name' => 'Aye', 'last_name' => 'Moe']);
        $staffTwo = Staff::factory()->create(['first_name' => 'Ko', 'last_name' => 'Min']);

        $response = $this->actingAs($user)->post(route('student-id-cards.bulk-print'), [
            'audience' => 'staff',
            'selected_ids' => [$staffOne->id, $staffTwo->id],
        ]);

        $response->assertOk();
        $response->assertSee('Aye Moe');
        $response->assertSee('Ko Min');
    }

    public function test_printable_id_cards_use_vertical_card_dimensions_by_default(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create(['name_en' => 'Aye Chan']);

        $response = $this->actingAs($user)->get(route('student-id-cards.print', $student));

        $response->assertOk();
        $response->assertSee('--card-width: 5.4cm;', false);
        $response->assertSee('--card-height: 8.5cm;', false);
        $response->assertDontSee('horizontal', false);
    }

    public function test_printable_id_cards_respect_saved_visible_field_settings(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create([
            'name_en' => 'Aye Chan',
            'admission_no' => 'STU-1001',
        ]);

        AppSetting::setValue('id_cards.student_fields', json_encode(['student_id', 'guardian']));

        $response = $this->actingAs($user)->get(route('student-id-cards.print', $student));

        $response->assertOk();
        $response->assertSee('Student ID');
        $response->assertSee('Guardian');
        $response->assertDontSee('Date of Birth');
        $response->assertDontSee('Contact Number');
    }

    public function test_student_id_card_uses_selected_student_card_color_theme(): void
    {
        $user = User::factory()->create();
        $student = Student::factory()->create([
            'name_en' => 'Aye Chan',
            'card_color' => 'red',
        ]);

        $response = $this->actingAs($user)->get(route('student-id-cards.print', $student));

        $response->assertOk();
        $response->assertSee('--accent: #d62828;', false);
        $response->assertSee('--footer-bottom: #d62828;', false);
    }

    private function fakeImageUpload(string $name): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+aF9sAAAAASUVORK5CYII=')
        );
    }
}
