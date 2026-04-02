<?php

namespace App\Services\TemplateEngine\Adapters;

class DocxTemplateAdapter extends AbstractTemplateAdapter
{
    protected array $extensions = ['docx'];

    public function capabilities(): array
    {
        return [
            'format' => 'docx',
            'supports_markers' => true,
            'supports_label_heuristics' => false,
            'supports_iterable_assets' => true,
            'supports_signature_embedding' => false,
            'notes' => 'Reliable when templates use explicit markers.',
        ];
    }
}

