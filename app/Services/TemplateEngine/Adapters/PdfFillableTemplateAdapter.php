<?php

namespace App\Services\TemplateEngine\Adapters;

class PdfFillableTemplateAdapter extends AbstractTemplateAdapter
{
    protected array $extensions = ['pdf'];

    public function capabilities(): array
    {
        return [
            'format' => 'pdf',
            'supports_markers' => false,
            'supports_label_heuristics' => false,
            'supports_iterable_assets' => false,
            'supports_signature_embedding' => true,
            'requires_acroform' => true,
            'notes' => 'Designed for fillable PDFs. OCR fallback is intentionally out of scope here.',
        ];
    }
}

