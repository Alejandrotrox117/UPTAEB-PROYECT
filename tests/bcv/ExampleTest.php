<?php
use PHPUnit\Framework\TestCase;
class ExampleTest extends TestCase
{
    private function showMessage(string $msg): void
    {
        fwrite(STDOUT, "[MODEL MESSAGE] " . $msg . PHP_EOL);
    }
    public function testTrueIsTrue()
    {
        $this->assertTrue(true);
    }
}
