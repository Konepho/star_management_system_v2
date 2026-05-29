<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateAdminSettingsRequest;
use App\Models\AppSetting;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminSettingsController extends Controller
{
    public function edit(): View
    {
        $studentFieldOptions = config('id_cards.student_fields', []);
        $staffFieldOptions = config('id_cards.staff_fields', []);

        return view('admin-settings.edit', [
            'settings' => [
                'invoice_prefix' => AppSetting::getValue('documents.numbering.invoice.prefix', (string) config('documents.numbering.invoice.prefix')),
                'invoice_padding' => (int) AppSetting::getValue('documents.numbering.invoice.padding', (string) config('documents.numbering.invoice.padding')),
                'invoice_reset_scope' => AppSetting::getValue('documents.numbering.invoice.reset_scope', (string) config('documents.numbering.invoice.reset_scope')),
                'receipt_prefix' => AppSetting::getValue('documents.numbering.receipt.prefix', (string) config('documents.numbering.receipt.prefix')),
                'receipt_padding' => (int) AppSetting::getValue('documents.numbering.receipt.padding', (string) config('documents.numbering.receipt.padding')),
                'receipt_reset_scope' => AppSetting::getValue('documents.numbering.receipt.reset_scope', (string) config('documents.numbering.receipt.reset_scope')),
                'school_name' => AppSetting::getValue('invoice.school_name', 'STAR School'),
                'school_phone' => AppSetting::getValue('invoice.school_phone', ''),
                'school_email' => AppSetting::getValue('invoice.school_email', ''),
                'school_address' => AppSetting::getValue('invoice.school_address', ''),
                'invoice_name_format' => AppSetting::getValue('invoice.student_name_format', 'preferred_then_english'),
                'school_logo_path' => AppSetting::getValue('invoice.school_logo_path', ''),
                'student_id_card_fields' => $this->decodeFieldSelection(
                    AppSetting::getValue('id_cards.student_fields'),
                    array_keys($studentFieldOptions),
                ),
                'staff_id_card_fields' => $this->decodeFieldSelection(
                    AppSetting::getValue('id_cards.staff_fields'),
                    array_keys($staffFieldOptions),
                ),
            ],
            'studentFieldOptions' => $studentFieldOptions,
            'staffFieldOptions' => $staffFieldOptions,
        ]);
    }

    public function update(UpdateAdminSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $auditLogService = app(AuditLogService::class);
        $trackedKeys = [
            'documents.numbering.invoice.prefix',
            'documents.numbering.invoice.padding',
            'documents.numbering.invoice.reset_scope',
            'documents.numbering.receipt.prefix',
            'documents.numbering.receipt.padding',
            'documents.numbering.receipt.reset_scope',
            'invoice.school_name',
            'invoice.school_phone',
            'invoice.school_email',
            'invoice.school_address',
            'invoice.student_name_format',
            'invoice.school_logo_path',
            'id_cards.student_fields',
            'id_cards.staff_fields',
        ];
        $oldValues = collect($trackedKeys)
            ->mapWithKeys(fn (string $key) => [$key => AppSetting::getValue($key)])
            ->all();

        AppSetting::setValue('documents.numbering.invoice.prefix', $validated['invoice_prefix']);
        AppSetting::setValue('documents.numbering.invoice.padding', $validated['invoice_padding']);
        AppSetting::setValue('documents.numbering.invoice.reset_scope', $validated['invoice_reset_scope']);
        AppSetting::setValue('documents.numbering.receipt.prefix', $validated['receipt_prefix']);
        AppSetting::setValue('documents.numbering.receipt.padding', $validated['receipt_padding']);
        AppSetting::setValue('documents.numbering.receipt.reset_scope', $validated['receipt_reset_scope']);
        AppSetting::setValue('invoice.school_name', $validated['school_name']);
        AppSetting::setValue('invoice.school_phone', $validated['school_phone'] ?? '');
        AppSetting::setValue('invoice.school_email', $validated['school_email'] ?? '');
        AppSetting::setValue('invoice.school_address', $validated['school_address'] ?? '');
        AppSetting::setValue('invoice.student_name_format', $validated['invoice_name_format']);
        AppSetting::setValue('id_cards.student_fields', json_encode(array_values($validated['student_id_card_fields'])));
        AppSetting::setValue('id_cards.staff_fields', json_encode(array_values($validated['staff_id_card_fields'])));

        $existingLogoPath = AppSetting::getValue('invoice.school_logo_path');

        if ($request->boolean('remove_school_logo') && $existingLogoPath) {
            Storage::disk('public')->delete($existingLogoPath);
            AppSetting::deleteValue('invoice.school_logo_path');
        }

        if ($request->hasFile('school_logo')) {
            if ($existingLogoPath) {
                Storage::disk('public')->delete($existingLogoPath);
            }

            $logoPath = $request->file('school_logo')->store('settings', 'public');
            AppSetting::setValue('invoice.school_logo_path', $logoPath);
        }

        $newValues = collect($trackedKeys)
            ->mapWithKeys(fn (string $key) => [$key => AppSetting::getValue($key)])
            ->all();
        [$changedOldValues, $changedNewValues] = $auditLogService->changedValues($oldValues, $newValues);

        if ($changedOldValues !== [] || $changedNewValues !== []) {
            $auditLogService->log(
                'settings',
                'admin_settings',
                'updated',
                null,
                $changedOldValues,
                $changedNewValues,
                'Updated admin settings.',
                ['changed_keys' => array_keys($changedNewValues)]
            );
        }

        return redirect()
            ->route('admin-settings.edit')
            ->with('status', 'Admin settings updated successfully.');
    }

    private function decodeFieldSelection(mixed $storedValue, array $default): array
    {
        if (! is_string($storedValue) || trim($storedValue) === '') {
            return $default;
        }

        $decoded = json_decode($storedValue, true);

        if (! is_array($decoded)) {
            return $default;
        }

        return array_values(array_filter($decoded, fn (mixed $value) => is_string($value) && $value !== ''));
    }
}
