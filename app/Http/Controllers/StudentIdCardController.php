<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\Enrollment;
use App\Models\Grade;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StudentIdCardController extends Controller
{
    public function index(Request $request): View
    {
        $requestedAudience = $request->string('audience')->toString() === 'staff' ? 'staff' : 'students';
        $audience = $this->resolvedAudience($requestedAudience);
        $search = trim((string) $request->string('search')->toString());
        $academicYearId = $request->integer('academic_year_id') ?: null;
        $gradeId = $request->integer('grade_id') ?: null;
        $sectionId = $request->integer('section_id') ?: null;
        $department = $request->string('department')->toString() ?: null;

        $students = collect();
        $staff = collect();

        if ($audience === 'students') {
            $studentsQuery = Student::query()
                ->with([
                    'activeEnrollments' => fn ($query) => $query
                        ->with(['academicYear', 'grade', 'section'])
                        ->orderByDesc('enrollment_date')
                        ->orderByDesc('id'),
                ]);

            if ($search !== '') {
                $studentsQuery->where(function ($query) use ($search) {
                    $query->where('admission_no', 'like', '%' . $search . '%')
                        ->orWhere('name_en', 'like', '%' . $search . '%')
                        ->orWhere('name_mm', 'like', '%' . $search . '%')
                        ->orWhere('preferred_name', 'like', '%' . $search . '%');
                });
            }

            if ($academicYearId || $gradeId || $sectionId) {
                $studentsQuery->whereHas('activeEnrollments', function ($query) use ($academicYearId, $gradeId, $sectionId) {
                    if ($academicYearId) {
                        $query->where('academic_year_id', $academicYearId);
                    }

                    if ($gradeId) {
                        $query->where('grade_id', $gradeId);
                    }

                    if ($sectionId) {
                        $query->where('section_id', $sectionId);
                    }
                });
            }

            $students = $studentsQuery
                ->orderBy('name_en')
                ->orderBy('admission_no')
                ->get();
        } else {
            $staffQuery = Staff::query()->with('user');

            if ($search !== '') {
                $staffQuery->where(function ($query) use ($search) {
                    $query->where('staff_no', 'like', '%' . $search . '%')
                        ->orWhere('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('designation', 'like', '%' . $search . '%');
                });
            }

            if ($department) {
                $staffQuery->where('department', $department);
            }

            $staff = $staffQuery
                ->orderBy('department')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
        }

        return view('student-id-cards.index', [
            'audience' => $audience,
            'students' => $students,
            'staff' => $staff,
            'academicYears' => AcademicYear::query()->orderByDesc('start_date')->get(),
            'grades' => Grade::query()->orderBy('sort_order')->orderBy('name')->get(),
            'sections' => Section::query()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
            'departments' => Staff::query()->select('department')->whereNotNull('department')->distinct()->orderBy('department')->pluck('department'),
            'search' => $search,
            'selectedAcademicYearId' => $academicYearId,
            'selectedGradeId' => $gradeId,
            'selectedSectionId' => $sectionId,
            'selectedDepartment' => $department,
        ]);
    }

    public function show(Student $student): View
    {
        $cards = collect([
            $this->studentCardRecord(
                $student->load([
                    'activeEnrollments' => fn ($query) => $query
                        ->with(['academicYear', 'grade', 'section'])
                        ->orderByDesc('enrollment_date')
                        ->orderByDesc('id'),
                    'guardians',
                ])
            ),
        ]);

        return view('student-id-cards.print', [
            'cards' => $cards,
            'cardSettings' => $this->cardSettings(),
        ]);
    }

    public function printStaff(Staff $staff): View
    {
        return view('student-id-cards.print', [
            'cards' => collect([$this->staffCardRecord($staff->load('user'))]),
            'cardSettings' => $this->cardSettings(),
        ]);
    }

    public function bulkPrint(Request $request): View|RedirectResponse
    {
        $audience = $request->string('audience')->toString() === 'staff' ? 'staff' : 'students';

        $this->authorizeAudience($audience);

        $validated = $request->validate([
            'audience' => ['required', 'in:students,staff'],
            'selected_ids' => ['required', 'array', 'min:1'],
            'selected_ids.*' => ['integer'],
        ]);

        if ($audience === 'students') {
            $students = Student::query()
                ->with([
                    'activeEnrollments' => fn ($query) => $query
                        ->with(['academicYear', 'grade', 'section'])
                        ->orderByDesc('enrollment_date')
                        ->orderByDesc('id'),
                    'guardians',
                ])
                ->whereIn('id', $validated['selected_ids'])
                ->orderBy('name_en')
                ->orderBy('admission_no')
                ->get();

            if ($students->isEmpty()) {
                return redirect()
                    ->route('student-id-cards.index', ['audience' => 'students'])
                    ->withErrors(['selected_ids' => 'Select at least one student to print ID cards.']);
            }

            $cards = $students->map(fn (Student $student) => $this->studentCardRecord($student));
        } else {
            $staff = Staff::query()
                ->with('user')
                ->whereIn('id', $validated['selected_ids'])
                ->orderBy('department')
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

            if ($staff->isEmpty()) {
                return redirect()
                    ->route('student-id-cards.index', ['audience' => 'staff'])
                    ->withErrors(['selected_ids' => 'Select at least one staff member to print ID cards.']);
            }

            $cards = $staff->map(fn (Staff $member) => $this->staffCardRecord($member));
        }

        return view('student-id-cards.print', [
            'cards' => $cards,
            'cardSettings' => $this->cardSettings(),
        ]);
    }

    private function resolvedAudience(string $requestedAudience): string
    {
        $canPrintStudents = $this->canPrintStudentCards();
        $canPrintStaff = $this->canPrintStaffCards();

        abort_unless($canPrintStudents || $canPrintStaff, 403);

        if ($requestedAudience === 'staff') {
            return $canPrintStaff ? 'staff' : 'students';
        }

        return $canPrintStudents ? 'students' : 'staff';
    }

    private function authorizeAudience(string $audience): void
    {
        abort_unless(
            $audience === 'staff' ? $this->canPrintStaffCards() : $this->canPrintStudentCards(),
            403
        );
    }

    private function canPrintStudentCards(): bool
    {
        return Auth::user()?->hasPermission('id_cards.students.print') ?? false;
    }

    private function canPrintStaffCards(): bool
    {
        return Auth::user()?->hasPermission('id_cards.staff.print') ?? false;
    }

    private function cardSettings(): array
    {
        $nameFormat = AppSetting::getValue('invoice.student_name_format', 'preferred_then_english');

        return [
            'school_name' => AppSetting::getValue('invoice.school_name', 'STAR School'),
            'school_phone' => AppSetting::getValue('invoice.school_phone', ''),
            'school_email' => AppSetting::getValue('invoice.school_email', ''),
            'school_address' => AppSetting::getValue('invoice.school_address', ''),
            'school_logo_data_url' => $this->logoDataUrl(AppSetting::getValue('invoice.school_logo_path')),
            'student_name_format' => $nameFormat,
            'layout' => 'vertical',
        ];
    }

    private function studentCardRecord(Student $student): array
    {
        $enrollment = $student->currentEnrollment();
        $guardian = $student->guardians->firstWhere('is_primary_contact', true)
            ?? $student->fatherGuardian
            ?? $student->motherGuardian
            ?? $student->guardians->first();
        $contactNumber = $guardian?->phone ?: $student->contact_number ?: $student->phone ?: $student->emergency_contact_number;
        $displayName = $this->studentDisplayName($student);
        $secondaryName = $student->name_mm ?: 'No Burmese name';

        if (trim(mb_strtolower($secondaryName)) === trim(mb_strtolower($displayName))) {
            $secondaryName = '';
        }

        return [
            'audience' => 'student',
            'display_name' => $displayName,
            'secondary_name' => $secondaryName,
            'identifier_label' => 'Admission No',
            'identifier_value' => $student->admission_no ?: '—',
            'barcode_value' => $student->admission_no ?: '—',
            'barcode_svg' => $student->admission_no ? $this->code39Svg($student->admission_no) : null,
            'status' => ucfirst($student->status),
            'primary_badge' => 'Student ID Card',
            'details' => $this->buildStudentCardDetails($student, $enrollment, $guardian?->name, $contactNumber),
            'photo_data_url' => $this->fileDataUrl($student->photo_path),
            'avatar_text' => $this->avatarText($student->preferred_name ?: $student->name_en ?: $student->admission_no),
            'theme' => $this->cardTheme($student->card_color ?: 'yellow'),
        ];
    }

    private function staffCardRecord(Staff $staff): array
    {
        $displayName = $staff->displayName();

        return [
            'audience' => 'staff',
            'display_name' => $displayName !== '' ? $displayName : '—',
            'secondary_name' => $staff->designation ?: ($staff->department ?: 'Staff member'),
            'identifier_label' => 'Staff No',
            'identifier_value' => $staff->staff_no ?: '—',
            'barcode_value' => $staff->staff_no ?: '—',
            'barcode_svg' => $staff->staff_no ? $this->code39Svg($staff->staff_no) : null,
            'status' => ucfirst($staff->status),
            'primary_badge' => 'Staff ID Card',
            'details' => $this->buildStaffCardDetails($staff),
            'photo_data_url' => $this->fileDataUrl($staff->photo_path),
            'avatar_text' => $this->avatarText($displayName ?: $staff->staff_no),
            'theme' => $this->cardTheme('purple'),
        ];
    }

    private function buildStudentCardDetails(Student $student, ?Enrollment $enrollment, ?string $guardianName, ?string $contactNumber): array
    {
        $fieldKeys = $this->idCardFieldSelection('student', array_keys(config('id_cards.student_fields', [])));
        $values = [
            'grade' => trim(collect([
                $enrollment?->grade?->name,
                $enrollment?->section?->name ? '(' . $enrollment->section->name . ')' : null,
            ])->filter()->implode(' ')) ?: '—',
            'student_id' => $student->admission_no ?: '—',
            'date_of_birth' => optional($student->date_of_birth)->format('d M Y') ?: '—',
            'guardian' => $guardianName ?: '—',
            'contact_number' => $contactNumber ?: '—',
        ];

        return collect(config('id_cards.student_fields', []))
            ->only($fieldKeys)
            ->map(fn (string $label, string $key) => [
                'label' => $label,
                'value' => $values[$key] ?? '—',
            ])
            ->values()
            ->all();
    }

    private function buildStaffCardDetails(Staff $staff): array
    {
        $fieldKeys = $this->idCardFieldSelection('staff', array_keys(config('id_cards.staff_fields', [])));
        $values = [
            'department' => $staff->department ?: '—',
            'designation' => $staff->designation ?: '—',
            'join_date' => optional($staff->join_date)->format('d M Y') ?: '—',
            'phone' => $staff->phone ?: '—',
            'email' => $staff->email ?: '—',
            'username' => $staff->user?->username ?: '—',
        ];

        return collect(config('id_cards.staff_fields', []))
            ->only($fieldKeys)
            ->map(fn (string $label, string $key) => [
                'label' => $label,
                'value' => $values[$key] ?? '—',
            ])
            ->values()
            ->all();
    }

    private function idCardFieldSelection(string $audience, array $default): array
    {
        $storedValue = AppSetting::getValue('id_cards.' . $audience . '_fields');

        if (! is_string($storedValue) || trim($storedValue) === '') {
            return $default;
        }

        $decoded = json_decode($storedValue, true);

        if (! is_array($decoded)) {
            return $default;
        }

        $validKeys = array_values(array_filter(
            $decoded,
            fn (mixed $value) => is_string($value) && in_array($value, $default, true)
        ));

        return $validKeys !== [] ? $validKeys : $default;
    }

    private function cardTheme(string $color): array
    {
        return match ($color) {
            'red' => [
                'accent' => '#d62828',
                'accent_dark' => '#b61f1f',
                'accent_light' => '#f87171',
                'footer_top' => '#ef4444',
                'footer_bottom' => '#d62828',
                'brand' => '#9f1239',
            ],
            'blue' => [
                'accent' => '#2563eb',
                'accent_dark' => '#1d4ed8',
                'accent_light' => '#60a5fa',
                'footer_top' => '#3b82f6',
                'footer_bottom' => '#2563eb',
                'brand' => '#1e3a8a',
            ],
            'green' => [
                'accent' => '#16a34a',
                'accent_dark' => '#15803d',
                'accent_light' => '#4ade80',
                'footer_top' => '#22c55e',
                'footer_bottom' => '#16a34a',
                'brand' => '#166534',
            ],
            'orange' => [
                'accent' => '#ea580c',
                'accent_dark' => '#c2410c',
                'accent_light' => '#fb923c',
                'footer_top' => '#f97316',
                'footer_bottom' => '#ea580c',
                'brand' => '#9a3412',
            ],
            'purple' => [
                'accent' => '#7c3aed',
                'accent_dark' => '#6d28d9',
                'accent_light' => '#a78bfa',
                'footer_top' => '#8b5cf6',
                'footer_bottom' => '#7c3aed',
                'brand' => '#5b21b6',
            ],
            default => [
                'accent' => '#f2bf08',
                'accent_dark' => '#dbab00',
                'accent_light' => '#f8cf33',
                'footer_top' => '#f6c51a',
                'footer_bottom' => '#f2bf08',
                'brand' => '#6d5b15',
            ],
        };
    }

    private function studentDisplayName(Student $student): string
    {
        return match ($this->cardSettings()['student_name_format']) {
            'english_only' => $student->name_en ?: $student->full_name,
            'bilingual' => trim(collect([$student->name_en ?: $student->full_name, $student->name_mm])->filter()->implode(' / ')),
            default => trim(collect([$student->preferred_name, $student->name_en ?: $student->full_name])->filter()->unique()->implode(' / ')),
        } ?: '—';
    }

    private function avatarText(?string $value): string
    {
        return strtoupper(substr(trim((string) $value), 0, 2)) ?: 'ID';
    }

    private function logoDataUrl(?string $logoPath): ?string
    {
        return $this->fileDataUrl($logoPath);
    }

    private function fileDataUrl(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $fileContents = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path) ?: 'image/png';

        return 'data:' . $mimeType . ';base64,' . base64_encode($fileContents);
    }

    private function code39Svg(string $value): string
    {
        $value = strtoupper(trim($value));

        $patterns = [
            '0' => 'nnnwwnwnn', '1' => 'wnnwnnnnw', '2' => 'nnwwnnnnw', '3' => 'wnwwnnnnn',
            '4' => 'nnnwwnnnw', '5' => 'wnnwwnnnn', '6' => 'nnwwwnnnn', '7' => 'nnnwnnwnw',
            '8' => 'wnnwnnwnn', '9' => 'nnwwnnwnn', 'A' => 'wnnnnwnnw', 'B' => 'nnwnnwnnw',
            'C' => 'wnwnnwnnn', 'D' => 'nnnnwwnnw', 'E' => 'wnnnwwnnn', 'F' => 'nnwnwwnnn',
            'G' => 'nnnnnwwnw', 'H' => 'wnnnnwwnn', 'I' => 'nnwnnwwnn', 'J' => 'nnnnwwwnn',
            'K' => 'wnnnnnnww', 'L' => 'nnwnnnnww', 'M' => 'wnwnnnnwn', 'N' => 'nnnnwnnww',
            'O' => 'wnnnwnnwn', 'P' => 'nnwnwnnwn', 'Q' => 'nnnnnnwww', 'R' => 'wnnnnnwwn',
            'S' => 'nnwnnnwwn', 'T' => 'nnnnwnwwn', 'U' => 'wwnnnnnnw', 'V' => 'nwwnnnnnw',
            'W' => 'wwwnnnnnn', 'X' => 'nwnnwnnnw', 'Y' => 'wwnnwnnnn', 'Z' => 'nwwnwnnnn',
            '-' => 'nwnnnnwnw', '.' => 'wwnnnnwnn', ' ' => 'nwwnnnwnn', '$' => 'nwnwnwnnn',
            '/' => 'nwnwnnnwn', '+' => 'nwnnnwnwn', '%' => 'nnnwnwnwn', '*' => 'nwnnwnwnn',
        ];

        $encoded = '*' . $value . '*';
        $x = 0;
        $narrow = 2;
        $wide = 5;
        $height = 54;
        $parts = [];

        foreach (str_split($encoded) as $char) {
            $pattern = $patterns[$char] ?? $patterns['-'];

            foreach (str_split($pattern) as $index => $widthType) {
                $width = $widthType === 'w' ? $wide : $narrow;

                if ($index % 2 === 0) {
                    $parts[] = '<rect x="' . $x . '" y="0" width="' . $width . '" height="' . $height . '" fill="#111111" />';
                }

                $x += $width;
            }

            $x += $narrow;
        }

        return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $x . ' ' . $height . '" preserveAspectRatio="none" aria-hidden="true">' . implode('', $parts) . '</svg>';
    }
}
