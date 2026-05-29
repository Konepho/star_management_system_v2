<?php

return [
    'numbering' => [
        'invoice' => [
            'prefix' => 'INV',
            'padding' => 5,
            'reset_scope' => 'academic_year',
        ],
        'receipt' => [
            'prefix' => 'RCPT',
            'padding' => 5,
            'reset_scope' => 'academic_year',
        ],
        'external_exam_receipt' => [
            'prefix' => 'EXAM-RCPT',
            'padding' => 5,
            'reset_scope' => 'academic_year',
        ],
        'wallet_topup_receipt' => [
            'prefix' => 'TOPUP-RCPT',
            'padding' => 5,
            'reset_scope' => 'academic_year',
        ],
        'pos_sale' => [
            'prefix' => 'POS',
            'padding' => 5,
            'reset_scope' => 'academic_year',
        ],
    ],
];
