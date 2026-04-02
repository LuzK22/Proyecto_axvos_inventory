<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Hybrid Template Engine
    |--------------------------------------------------------------------------
    |
    | This module is intentionally disabled by default so existing document
    | generation behavior remains unchanged. When enabled, it can be wired
    | progressively to Acta flows with feature flags.
    |
    */
    'enabled' => env('TEMPLATE_ENGINE_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Adapter Strategy
    |--------------------------------------------------------------------------
    |
    | Order matters: first matching adapter is used.
    |
    */
    'adapters' => [
        'xlsx' => [
            'class' => \App\Services\TemplateEngine\Adapters\XlsxTemplateAdapter::class,
            'extensions' => ['xlsx'],
        ],
        'docx' => [
            'class' => \App\Services\TemplateEngine\Adapters\DocxTemplateAdapter::class,
            'extensions' => ['docx'],
        ],
        'pdf_fillable' => [
            'class' => \App\Services\TemplateEngine\Adapters\PdfFillableTemplateAdapter::class,
            'extensions' => ['pdf'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Confidence Thresholds
    |--------------------------------------------------------------------------
    |
    | Reserved for gradual rollout and visual mapper fallback.
    |
    */
    'confidence' => [
        'auto_accept' => 0.85,
        'needs_review' => 0.60,
    ],
];

