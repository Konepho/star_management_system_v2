<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\AppSetting;
use App\Models\DocumentSequence;
use Illuminate\Support\Facades\DB;

class DocumentNumberService
{
    public function nextInvoiceNumber(?AcademicYear $academicYear = null): string
    {
        return $this->nextNumber('invoice', $academicYear);
    }

    public function nextReceiptNumber(?AcademicYear $academicYear = null): string
    {
        return $this->nextNumber('receipt', $academicYear);
    }

    public function nextExternalExamReceiptNumber(?AcademicYear $academicYear = null): string
    {
        return $this->nextNumber('external_exam_receipt', $academicYear);
    }

    public function nextWalletTopupReceiptNumber(?AcademicYear $academicYear = null): string
    {
        return $this->nextNumber('wallet_topup_receipt', $academicYear);
    }

    public function nextPosSaleNumber(?AcademicYear $academicYear = null): string
    {
        return $this->nextNumber('pos_sale', $academicYear);
    }

    protected function nextNumber(string $documentType, ?AcademicYear $academicYear = null): string
    {
        $config = $this->numberingConfig($documentType);
        $prefix = $config['prefix'];
        $padding = $config['padding'];
        $resetScope = $config['reset_scope'];

        $scopeKey = $resetScope === 'academic_year'
            ? ($academicYear?->name ?? 'general')
            : 'global';

        $nextNumber = DB::transaction(function () use ($documentType, $scopeKey): int {
            $sequence = DocumentSequence::query()
                ->where('document_type', $documentType)
                ->where('scope_key', $scopeKey)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = DocumentSequence::query()->create([
                    'document_type' => $documentType,
                    'scope_key' => $scopeKey,
                    'next_number' => 2,
                ]);

                return 1;
            }

            $currentNumber = (int) $sequence->next_number;

            $sequence->update([
                'next_number' => $currentNumber + 1,
            ]);

            return $currentNumber;
        });

        $number = str_pad((string) $nextNumber, $padding, '0', STR_PAD_LEFT);

        if ($resetScope === 'academic_year' && $academicYear?->name) {
            return "{$prefix}/{$academicYear->name}/{$number}";
        }

        return "{$prefix}/{$number}";
    }

    public function numberingConfig(string $documentType): array
    {
        $config = config("documents.numbering.{$documentType}");

        return [
            'prefix' => AppSetting::getValue("documents.numbering.{$documentType}.prefix", (string) ($config['prefix'] ?? strtoupper($documentType))),
            'padding' => (int) AppSetting::getValue("documents.numbering.{$documentType}.padding", (string) ($config['padding'] ?? 5)),
            'reset_scope' => AppSetting::getValue("documents.numbering.{$documentType}.reset_scope", (string) ($config['reset_scope'] ?? 'global')),
        ];
    }
}
