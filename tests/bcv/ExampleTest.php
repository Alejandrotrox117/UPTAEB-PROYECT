<?php

namespace Tests\Bcv;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ExampleTest extends TestCase
{
    #[Test]
    public function testTrueIsTrue(): void
    {
        $this->assertTrue(true);
    }
}
