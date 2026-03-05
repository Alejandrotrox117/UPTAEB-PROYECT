<?php

namespace Tests\IntegrationTest\BCV;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

require_once __DIR__ . '/../../Traits/RequiresDatabase.php';

class ExampleIntegrationTest extends TestCase
{
    use \Tests\Traits\RequiresDatabase;

    public function setUp(): void
    {
        $this->requireDatabase();
    }

    #[Test]
    public function testTrueIsTrue(): void
    {
        $this->assertTrue(true);
    }
}
