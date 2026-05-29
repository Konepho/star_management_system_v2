<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\User;
use App\Services\DocumentNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminSettingsManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_admin_settings_page(): void
    {
        $this->get(route('admin-settings.edit'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_view_admin_settings_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('admin-settings.edit'));

        $response->assertOk();
        $response->assertSee('Admin Settings');
        $response->assertSee('Invoice Numbering');
        $response->assertSee('Receipt Numbering');
        $response->assertSee('Printable Invoice Settings');
        $response->assertSee('ID Card Fields');
        $response->assertSee('Student Card Fields');
        $response->assertSee('Staff Card Fields');
    }

    public function test_authenticated_users_can_update_document_numbering_settings(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put(route('admin-settings.update'), [
            'invoice_prefix' => 'SMS-INV',
            'invoice_padding' => 6,
            'invoice_reset_scope' => 'global',
            'receipt_prefix' => 'SMS-RCPT',
            'receipt_padding' => 4,
            'receipt_reset_scope' => 'academic_year',
            'school_name' => 'STAR School',
            'school_phone' => '',
            'school_email' => '',
            'school_address' => '',
            'invoice_name_format' => 'preferred_then_english',
            'student_id_card_fields' => ['grade', 'student_id', 'date_of_birth'],
            'staff_id_card_fields' => ['department', 'designation', 'phone'],
        ]);

        $response->assertRedirect(route('admin-settings.edit'));
        $response->assertSessionHas('status', 'Admin settings updated successfully.');

        $this->assertSame('SMS-INV', AppSetting::getValue('documents.numbering.invoice.prefix'));
        $this->assertSame('6', AppSetting::getValue('documents.numbering.invoice.padding'));
        $this->assertSame('global', AppSetting::getValue('documents.numbering.invoice.reset_scope'));
        $this->assertSame('SMS-RCPT', AppSetting::getValue('documents.numbering.receipt.prefix'));
        $this->assertSame('4', AppSetting::getValue('documents.numbering.receipt.padding'));
        $this->assertSame('academic_year', AppSetting::getValue('documents.numbering.receipt.reset_scope'));
        $this->assertSame(json_encode(['grade', 'student_id', 'date_of_birth']), AppSetting::getValue('id_cards.student_fields'));
        $this->assertSame(json_encode(['department', 'designation', 'phone']), AppSetting::getValue('id_cards.staff_fields'));
    }

    public function test_document_numbers_follow_saved_admin_settings(): void
    {
        $user = User::factory()->create();
        $academicYear = AcademicYear::factory()->create(['name' => '2026-2027']);

        $this->actingAs($user)->put(route('admin-settings.update'), [
            'invoice_prefix' => 'SMS-INV',
            'invoice_padding' => 6,
            'invoice_reset_scope' => 'global',
            'receipt_prefix' => 'SMS-RCPT',
            'receipt_padding' => 4,
            'receipt_reset_scope' => 'academic_year',
            'school_name' => 'STAR School',
            'school_phone' => '',
            'school_email' => '',
            'school_address' => '',
            'invoice_name_format' => 'preferred_then_english',
            'student_id_card_fields' => ['grade', 'student_id', 'guardian'],
            'staff_id_card_fields' => ['department', 'designation', 'username'],
        ]);

        $service = app(DocumentNumberService::class);

        $invoiceNumber = $service->nextInvoiceNumber($academicYear);
        $receiptNumberOne = $service->nextReceiptNumber($academicYear);
        $receiptNumberTwo = $service->nextReceiptNumber($academicYear);

        $this->assertSame('SMS-INV/000001', $invoiceNumber);
        $this->assertSame('SMS-RCPT/2026-2027/0001', $receiptNumberOne);
        $this->assertSame('SMS-RCPT/2026-2027/0002', $receiptNumberTwo);
    }

    public function test_authenticated_users_can_update_printable_invoice_settings_with_logo(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $logo = UploadedFile::fake()->createWithContent(
            'school-logo.png',
            base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9sWwaP8AAAAASUVORK5CYII=')
        );

        $response = $this->actingAs($user)->put(route('admin-settings.update'), [
            'invoice_prefix' => 'INV',
            'invoice_padding' => 5,
            'invoice_reset_scope' => 'academic_year',
            'receipt_prefix' => 'RCPT',
            'receipt_padding' => 5,
            'receipt_reset_scope' => 'academic_year',
            'school_name' => 'STAR International School',
            'school_phone' => '09-123456789',
            'school_email' => 'info@star.edu.mm',
            'school_address' => 'Yangon, Myanmar',
            'invoice_name_format' => 'bilingual',
            'student_id_card_fields' => ['grade', 'student_id', 'contact_number'],
            'staff_id_card_fields' => ['department', 'designation', 'email'],
            'school_logo' => $logo,
        ]);

        $response->assertRedirect(route('admin-settings.edit'));

        $storedLogoPath = AppSetting::getValue('invoice.school_logo_path');

        $this->assertSame('STAR International School', AppSetting::getValue('invoice.school_name'));
        $this->assertSame('09-123456789', AppSetting::getValue('invoice.school_phone'));
        $this->assertSame('info@star.edu.mm', AppSetting::getValue('invoice.school_email'));
        $this->assertSame('Yangon, Myanmar', AppSetting::getValue('invoice.school_address'));
        $this->assertSame('bilingual', AppSetting::getValue('invoice.student_name_format'));
        $this->assertNotEmpty($storedLogoPath);
        Storage::disk('public')->assertExists($storedLogoPath);
    }
}
