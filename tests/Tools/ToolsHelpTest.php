<?php
// tests/ToolsTest.php

namespace Tools;

use PHPUnit\Framework\TestCase;
use Raiseinfo\Tools;

final class ToolsHelpTest extends TestCase
{
    public function testHelp(): void
    {
        $tools = new Tools();
        $this->assertEquals('this is a helper!', $tools->help());
    }
}