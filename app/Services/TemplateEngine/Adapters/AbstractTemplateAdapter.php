<?php

namespace App\Services\TemplateEngine\Adapters;

use App\Services\TemplateEngine\Contracts\TemplateAdapter;

abstract class AbstractTemplateAdapter implements TemplateAdapter
{
    /**
     * @var array<int, string>
     */
    protected array $extensions = [];

    public function supports(string $extension): bool
    {
        return in_array(strtolower($extension), $this->extensions, true);
    }
}

