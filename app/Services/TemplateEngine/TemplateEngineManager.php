<?php

namespace App\Services\TemplateEngine;

use App\Services\TemplateEngine\Contracts\TemplateAdapter;
use InvalidArgumentException;

class TemplateEngineManager
{
    /**
     * @var array<int, TemplateAdapter>
     */
    private array $adapters;

    /**
     * @param array<int, TemplateAdapter> $adapters
     */
    public function __construct(array $adapters)
    {
        $this->adapters = $adapters;
    }

    public static function fromConfig(): self
    {
        $configured = config('template_engine.adapters', []);
        $instances = [];

        foreach ($configured as $adapterConfig) {
            $class = $adapterConfig['class'] ?? null;
            if (!is_string($class) || !class_exists($class)) {
                continue;
            }

            $instances[] = app($class);
        }

        return new self($instances);
    }

    public function resolve(string $extension): TemplateAdapter
    {
        $normalized = strtolower(trim($extension, '. '));

        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($normalized)) {
                return $adapter;
            }
        }

        throw new InvalidArgumentException("No template adapter registered for extension [{$normalized}].");
    }

    /**
     * @return array<string, mixed>
     */
    public function capabilitiesFor(string $extension): array
    {
        return $this->resolve($extension)->capabilities();
    }
}

