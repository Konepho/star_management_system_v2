@php
    $invoiceExample = str_pad('1', (int) $settings['invoice_padding'], '0', STR_PAD_LEFT);
    $receiptExample = str_pad('1', (int) $settings['receipt_padding'], '0', STR_PAD_LEFT);
    $invoicePreview = $settings['invoice_reset_scope'] === 'academic_year'
        ? "{$settings['invoice_prefix']}/2026-2027/{$invoiceExample}"
        : "{$settings['invoice_prefix']}/{$invoiceExample}";
    $receiptPreview = $settings['receipt_reset_scope'] === 'academic_year'
        ? "{$settings['receipt_prefix']}/2026-2027/{$receiptExample}"
        : "{$settings['receipt_prefix']}/{$receiptExample}";
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ __('Admin Settings') }}</h2>
            <p class="mt-1 text-sm text-slate-700">Manage document numbering policy for invoices and receipts.</p>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('admin-settings.update') }}" enctype="multipart/form-data" class="space-y-8">
                        @csrf
                        @method('PUT')

                        <section class="space-y-4">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Printable Invoice Settings</h3>
                                <p class="mt-1 text-sm text-slate-500">Manage the school identity and invoice name display used on printable documents.</p>
                            </div>

                            <div class="grid gap-6 md:grid-cols-2">
                                <div>
                                    <x-input-label for="school_name" :value="__('School Name')" />
                                    <x-text-input id="school_name" name="school_name" type="text" class="mt-1 block w-full" :value="old('school_name', $settings['school_name'])" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('school_name')" />
                                </div>

                                <div>
                                    <x-input-label for="invoice_name_format" :value="__('Student Name Format')" />
                                    @php($nameFormat = old('invoice_name_format', $settings['invoice_name_format']))
                                    <select id="invoice_name_format" name="invoice_name_format" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="preferred_then_english" @selected($nameFormat === 'preferred_then_english')>Preferred / English / Burmese</option>
                                        <option value="bilingual" @selected($nameFormat === 'bilingual')>English / Burmese</option>
                                        <option value="english_only" @selected($nameFormat === 'english_only')>English Only</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('invoice_name_format')" />
                                </div>

                                <div>
                                    <x-input-label for="school_phone" :value="__('School Phone')" />
                                    <x-text-input id="school_phone" name="school_phone" type="text" class="mt-1 block w-full" :value="old('school_phone', $settings['school_phone'])" />
                                    <x-input-error class="mt-2" :messages="$errors->get('school_phone')" />
                                </div>

                                <div>
                                    <x-input-label for="school_email" :value="__('School Email')" />
                                    <x-text-input id="school_email" name="school_email" type="email" class="mt-1 block w-full" :value="old('school_email', $settings['school_email'])" />
                                    <x-input-error class="mt-2" :messages="$errors->get('school_email')" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="school_address" :value="__('School Address')" />
                                    <textarea id="school_address" name="school_address" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('school_address', $settings['school_address']) }}</textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('school_address')" />
                                </div>

                                <div class="md:col-span-2">
                                    <x-input-label for="school_logo" :value="__('School Logo')" />
                                    <input id="school_logo" name="school_logo" type="file" accept=".png,.jpg,.jpeg,.webp" class="mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm">
                                    <p class="mt-1 text-xs text-slate-500">Recommended: PNG, JPG, or WEBP. Maximum 2MB.</p>
                                    <x-input-error class="mt-2" :messages="$errors->get('school_logo')" />

                                    @if ($settings['school_logo_path'])
                                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-slate-700">
                                            <input type="checkbox" name="remove_school_logo" value="1" class="rounded border-gray-300 text-rose-600 shadow-sm focus:ring-rose-500">
                                            Remove current school logo
                                        </label>
                                    @endif
                                </div>
                            </div>
                        </section>

                        <section class="space-y-4 border-t border-slate-200 pt-6">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">ID Card Fields</h3>
                                <p class="mt-1 text-sm text-slate-500">Choose which rows appear on student and staff ID cards.</p>
                            </div>

                            <div class="grid gap-6 md:grid-cols-2">
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <h4 class="text-sm font-semibold text-slate-900">Student Card Fields</h4>
                                    <div class="mt-3 space-y-3">
                                        @php($selectedStudentFields = old('student_id_card_fields', $settings['student_id_card_fields']))
                                        @foreach ($studentFieldOptions as $fieldKey => $fieldLabel)
                                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                                <input
                                                    type="checkbox"
                                                    name="student_id_card_fields[]"
                                                    value="{{ $fieldKey }}"
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                    @checked(in_array($fieldKey, $selectedStudentFields, true))
                                                >
                                                <span>{{ $fieldLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('student_id_card_fields')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('student_id_card_fields.*')" />
                                </div>

                                <div class="rounded-lg border border-slate-200 p-4">
                                    <h4 class="text-sm font-semibold text-slate-900">Staff Card Fields</h4>
                                    <div class="mt-3 space-y-3">
                                        @php($selectedStaffFields = old('staff_id_card_fields', $settings['staff_id_card_fields']))
                                        @foreach ($staffFieldOptions as $fieldKey => $fieldLabel)
                                            <label class="flex items-center gap-3 text-sm text-slate-700">
                                                <input
                                                    type="checkbox"
                                                    name="staff_id_card_fields[]"
                                                    value="{{ $fieldKey }}"
                                                    class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                                    @checked(in_array($fieldKey, $selectedStaffFields, true))
                                                >
                                                <span>{{ $fieldLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <x-input-error class="mt-2" :messages="$errors->get('staff_id_card_fields')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('staff_id_card_fields.*')" />
                                </div>
                            </div>
                        </section>

                        <section class="space-y-4 border-t border-slate-200 pt-6">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Invoice Numbering</h3>
                                <p class="mt-1 text-sm text-slate-500">Configure the format used when new student invoices are created.</p>
                            </div>

                            <div class="grid gap-6 md:grid-cols-3">
                                <div>
                                    <x-input-label for="invoice_prefix" :value="__('Invoice Prefix')" />
                                    <x-text-input id="invoice_prefix" name="invoice_prefix" type="text" class="mt-1 block w-full" :value="old('invoice_prefix', $settings['invoice_prefix'])" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('invoice_prefix')" />
                                </div>

                                <div>
                                    <x-input-label for="invoice_padding" :value="__('Number Padding')" />
                                    <x-text-input id="invoice_padding" name="invoice_padding" type="number" min="1" max="10" class="mt-1 block w-full" :value="old('invoice_padding', $settings['invoice_padding'])" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('invoice_padding')" />
                                </div>

                                <div>
                                    <x-input-label for="invoice_reset_scope" :value="__('Reset Scope')" />
                                    @php($invoiceResetScope = old('invoice_reset_scope', $settings['invoice_reset_scope']))
                                    <select id="invoice_reset_scope" name="invoice_reset_scope" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="academic_year" @selected($invoiceResetScope === 'academic_year')>Academic Year</option>
                                        <option value="global" @selected($invoiceResetScope === 'global')>Global</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('invoice_reset_scope')" />
                                </div>
                            </div>
                        </section>

                        <section class="space-y-4 border-t border-slate-200 pt-6">
                            <div>
                                <h3 class="text-base font-semibold text-slate-900">Receipt Numbering</h3>
                                <p class="mt-1 text-sm text-slate-500">Configure the format used when payments are collected.</p>
                            </div>

                            <div class="grid gap-6 md:grid-cols-3">
                                <div>
                                    <x-input-label for="receipt_prefix" :value="__('Receipt Prefix')" />
                                    <x-text-input id="receipt_prefix" name="receipt_prefix" type="text" class="mt-1 block w-full" :value="old('receipt_prefix', $settings['receipt_prefix'])" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('receipt_prefix')" />
                                </div>

                                <div>
                                    <x-input-label for="receipt_padding" :value="__('Number Padding')" />
                                    <x-text-input id="receipt_padding" name="receipt_padding" type="number" min="1" max="10" class="mt-1 block w-full" :value="old('receipt_padding', $settings['receipt_padding'])" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('receipt_padding')" />
                                </div>

                                <div>
                                    <x-input-label for="receipt_reset_scope" :value="__('Reset Scope')" />
                                    @php($receiptResetScope = old('receipt_reset_scope', $settings['receipt_reset_scope']))
                                    <select id="receipt_reset_scope" name="receipt_reset_scope" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        <option value="academic_year" @selected($receiptResetScope === 'academic_year')>Academic Year</option>
                                        <option value="global" @selected($receiptResetScope === 'global')>Global</option>
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('receipt_reset_scope')" />
                                </div>
                            </div>
                        </section>

                        <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-4 text-sm text-sky-900">
                            Example invoice format with current settings: <span class="font-semibold">{{ $invoicePreview }}</span>
                            <br>
                            Example receipt format with current settings: <span class="font-semibold">{{ $receiptPreview }}</span>
                        </div>

                        <div class="flex items-center gap-3">
                            <x-primary-button>Save Settings</x-primary-button>
                            <a href="{{ route('dashboard') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
