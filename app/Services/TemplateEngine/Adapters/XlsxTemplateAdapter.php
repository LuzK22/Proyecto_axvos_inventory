<?php

namespace App\Services\TemplateEngine\Adapters;

class XlsxTemplateAdapter extends AbstractTemplateAdapter
{
    protected array $extensions = ['xlsx'];

    public function capabilities(): array
    {
        return [
            'format' => 'xlsx',
            'supports_markers' => true,
            'supports_label_heuristics' => true,
            'supports_iterable_assets' => true,
            'supports_signature_embedding' => false,
            'notes' => 'Best fit for enterprise templates with complex tabular sections.',
        ];
    }
}

