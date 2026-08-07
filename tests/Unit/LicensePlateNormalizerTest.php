<?php

namespace Tests\Unit;

use App\Services\LicensePlateNormalizer;
use PHPUnit\Framework\TestCase;

class LicensePlateNormalizerTest extends TestCase
{
    protected LicensePlateNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new LicensePlateNormalizer();
    }

    public function test_normalizes_plate_with_hyphens_and_spaces()
    {
        $this->assertEquals('ABC123', $this->normalizer->normalize('ABC-123'));
        $this->assertEquals('ABC123', $this->normalizer->normalize(' abc 123 '));
        $this->assertEquals('ABC123', $this->normalizer->normalize('A-B-C-1-2-3'));
    }

    public function test_normalizes_lowercase_and_special_characters()
    {
        $this->assertEquals('ABC123', $this->normalizer->normalize('abc-123!'));
        $this->assertEquals('XYZ999', $this->normalizer->normalize('.xyz_999#'));
    }

    public function test_normalizes_accented_characters()
    {
        $this->assertEquals('ABC123', $this->normalizer->normalize('ÁBC-123'));
    }
}
