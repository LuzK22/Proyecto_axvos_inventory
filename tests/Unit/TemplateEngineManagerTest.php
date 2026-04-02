<?php

namespace Tests\Unit;

use App\Services\TemplateEngine\Adapters\DocxTemplateAdapter;
use App\Services\TemplateEngine\Adapters\PdfFillableTemplateAdapter;
use App\Services\TemplateEngine\Adapters\XlsxTemplateAdapter;
use App\Services\TemplateEngine\TemplateEngineManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class TemplateEngineManagerTest extends TestCase
{
    public function test_resolve_returns_xlsx_adapter(): void
    {
        $manager = new TemplateEngineManager([
            new XlsxTemplateAdapter(),
            new DocxTemplateAdapter(),
            new PdfFillableTemplateAdapter(),
        ]);

        $adapter = $manager->resolve('xlsx');

        $this->assertInstanceOf(XlsxTemplateAdapter::class, $adapter);
    }

    public function test_capabilities_for_docx_include_markers_support(): void
    {
        $manager = new TemplateEngineManager([
            new XlsxTemplateAdapter(),
            new DocxTemplateAdapter(),
            new PdfFillableTemplateAdapter(),
        ]);

        $capabilities = $manager->capabilitiesFor('docx');

        $this->assertTrue($capabilities['supports_markers']);
        $this->assertFalse($capabilities['supports_label_heuristics']);
    }

    public function test_resolve_throws_for_unknown_extension(): void
    {
        $manager = new TemplateEngineManager([
            new XlsxTemplateAdapter(),
        ]);

        $this->expectException(InvalidArgumentException::class);
        $manager->resolve('odt');
    }
}

