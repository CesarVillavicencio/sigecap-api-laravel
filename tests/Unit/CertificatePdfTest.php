<?php

namespace Tests\Unit;

use App\Services\CertificatePdfService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CertificatePdfTest extends TestCase
{
    public function test_maximum_length_name_can_be_rendered(): void
    {
        $name = str_repeat('María ', 16).' '.str_repeat('Ñ', 100).' '.str_repeat('Á', 100);
        $pdf = app(CertificatePdfService::class)->generate($name);
        $this->assertStringStartsWith('%PDF-', $pdf);
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        app(CertificatePdfService::class)->generate('   ');
    }
}
