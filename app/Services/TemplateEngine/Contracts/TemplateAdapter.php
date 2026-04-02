<?php

namespace App\Services\TemplateEngine\Contracts;

interface TemplateAdapter
{
    /**
     * Returns true when this adapter can process the given extension.
     */
    public function supports(string $extension): bool;

    /**
     * Returns adapter capability summary for diagnostics and UI.
     *
     * @return array<string, mixed>
     */
    public function capabilities(): array;
}

